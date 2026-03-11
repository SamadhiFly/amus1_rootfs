<?php
namespace App\Utils\Traits;

/**
 * Trait Call
 * @package App\Utils\Traits
 */
trait Call
{

    /**
     * @var string
     */
    private $lockMsg = "用户正执行在写操作，请稍后重试。";


    /**
     * @var string
     */
    protected $output = '/tmp/gui_command_output.txt';


    /**
     * @var string
     */
    protected $log_dir = '/tmp/operator/gui/';


    /**
     * @var
     */
    private $lockFile;


    /**
     * @var array
     */
    private $contents = [];


    /**
     * @var null
     */
    private $backup = null;

    /**
     *
     * 流程：
     *  1.读取当前状态的配置信息
     *
     *  2.备份当前读取的数据信息
     *          acu_all_current_config.json，命名为 acu_all_new_config.json
     *  3.修改acu_all_new_config.json的参数
     *          修改参数
     *  4.写入修改状态，调用mjc命令
     *          mod_cli acu /tmp/command_output.txt mjc_acu_all /tmp/acu_all_current_config.json /tmp/acu_all_new_config.json
     *
     * @param $commands
     * @param $data
     * @return mixed
     */
    protected function syncCallCli($commands,$data)
    {
        if(empty($commands)) {
            return $this->error("指令为空，不做操作。");
        }

        $logName = $this->log_dir.date('Y-m-d',time()).'.log';
        if(!file_exists($logName)) {
            if(!is_dir($this->log_dir)) {
                mkdir('/tmp/operator/gui/',0777,true);
            }
            file_put_contents($logName,'------ init log | '.$logName . ' -------------'.PHP_EOL,8);
            exec('chown www-data:root '.$logName,$output,$return_code);
        }

        $this->lockFile = fopen($logName,'a+');

        if(!flock($this->lockFile,LOCK_EX|LOCK_NB)) {
            return $this->error($this->lockMsg);
        }

        $this->contents = [
            'user'      => $this->loginUserInfo,
            'params'    => $this->_params,
            'commands'  => $commands,
            'api'       => $this->_request->getUri()->getPath(),
            'process'   => [],
        ];

        try {
            foreach ($commands as $command) {
                //usleep(200);
                //默认读写
                if(isset($command['read_cmd'])&&isset($command['write_cmd'])) {
                    $this->readCli($command,$data,true);
                } else {

                    //只读
                    if(isset($command['read_cmd'])&&!isset($command['write_cmd'])) {
                        $this->readCli($command,$data);
                    }

                    //只写
                    if(!isset($command['read_cmd'])&&isset($command['write_cmd'])) {
                        $this->writeCli($command['write_cmd']);
                    }

                }
            }
        }catch (\Exception $e) {
            if(isset($this->backup)&&file_exists($this->backup)) {
                unlink($this->backup);
            }
            $msg = 'sync call cli exception: [ ' . $e->getMessage() . ' ]';
            array_push($this->contents['process'],['desc' => $msg]);
            $this->afterHandle($this->lockFile,json_encode($this->contents,true),$msg);
        }
        fwrite($this->lockFile,json_encode($this->contents) . PHP_EOL);
        flock($this->lockFile,LOCK_UN);//unlock
        fclose($this->lockFile);
        return true;
    }


    /**
     * 读取方法
     *
     * @param $command
     * @param $data
     * @param $readAndWrite
     */
    private function readCli($command,$data,$readAndWrite = false)
    {
        //1.读取当前状态的配置信息
        $result = exec($command['read_cmd'],$output,$return_code);
        array_push($this->contents['process'],[
            'desc'      => '读取 '. $command['read_cmd'],
            'code'      => $return_code,
            'output'    => $result
        ]);
        if(strpos($command['read_cmd'],'rc_acu_buc') !== false)
        {
            usleep(2000*1000);
            //array_push($this->contents['process'],['usleep']);
            if(!file_exists($command['file']))
            {
                //array_push($this->contents['process'], ['desc' => 'function_exists' . $command['file']]);
                return;
            }
        }
        else if(strpos($command['read_cmd'],'sc_mf_all') !== false)
        {
            usleep(100*1000);
            //array_push($this->contents['process'],['usleep']);
            if(!file_exists($command['file']))
            {
                //array_push($this->contents['process'], ['desc' => 'function_exists' . $command['file']]);
                return;
            }
        }
        else
        {
            usleep(100*1000);
        }

        if(($return_code == 0) ||  strpos($command['read_cmd'],'rc_acu_buc') !== false) {

            if($readAndWrite == true) {
                //2.备份读取数据 -> _new_.json
                $this->backup = '/tmp/'.str_replace("_", "_new_", base64_encode($command['file']).".json");
                if (!copy($command['file'], $this->backup)) {
                    $msg = '备份文件失败：' . $this->backup;
                    array_push($this->contents['process'], ['desc' => $msg]);
                    $this->afterHandle($this->lockFile, json_encode($this->contents, true), $msg);
                } else {
                    array_push($this->contents['process'], ['desc' => '备份文件成功：' . $this->backup]);
                }

                //3.修改内容
                $fileInfo = file_get_contents($this->backup, true);
                $fileInfo = json_decode($fileInfo, true);


                array_push($this->contents['process'],['desc' => $data]);

                $isChange = 0;
                foreach ($data as $field => $value) {
                    if (isset($fileInfo[$field])) {
                        $isChange = 1;
                        if(is_array($fileInfo[$field])) {
                            //$fileInfo[$field] = array_merge($fileInfo[$field],$value);
                            $fileInfo[$field] = $value;
                            //如果存在数组，只需要修改第一个 index = 0 , 否则不做修改。
                            //if(isset($fileInfo[$field][0])) {
                            //    $fileInfo[$field][0] = array_merge($fileInfo[$field][0],$value);
                            //}
                        } else {
                            $fileInfo[$field] = $value;
                        }
                    }
                }

                array_push($this->contents['process'],['desc' => $fileInfo]);
                if($isChange > 0) {
                    array_push($this->contents['process'],['desc' => '文件 '.$this->backup.' 值被更改']);

                    //保存修改内容
                    if(!file_put_contents($command['file'],json_encode($fileInfo,true))) {
                        unlink($this->backup);
                        $msg = '修改备份文件内容失败：'.$this->backup;
                        array_push($this->contents['process'],['desc' => $msg]);
                        $this->afterHandle($this->lockFile,json_encode($this->contents,true),$msg);
                    }

                    //4.调用修改cli
                    $itemWriteCmd = $command['write_cmd'] . ' ' . $command['file'] . ' ' . $command['file'];
                    $this->writeCli($itemWriteCmd);
                } else {
                    array_push($this->contents['process'],['desc' => '文件 '.$this->backup.' 值没有发生更改，不调用CLI']);
                }
            }
        } else {
            $msg = '调用命令失败，返回错误码 [ ' . $return_code . ' ]';
            array_push($this->contents['process'],['desc' => $msg]);
            $this->afterHandle($this->lockFile,json_encode($this->contents,true),$msg);
        }
    }


    /**
     * 写入方法
     *
     * @param $command
     */
    private function writeCli($command)
    {
        $resp = exec($command,$this->output,$update_return_code);

        if(strpos($command,'net_config') !== false)
        {
            usleep(8000*1000);
            array_push($this->contents['process'],['net_config usleep']);
        }
        else if(strpos($command,'ljc_acu_buc') !== false)
        {
            usleep(100*1000);
            array_push($this->contents['process'],['net_config usleep']);
        }
        array_push($this->contents['process'],[
            'desc'  =>  $command . '  返回值： '. $resp
        ]);

        if($update_return_code != 0) {
            if(strpos($command,'ljc_acu_buc') == false)
            {
                $msg = '调用修改命令失败，返回错误码 [ ' . $update_return_code . '  ]';
                array_push($this->contents['process'],['desc' => $msg]);
                $this->afterHandle($this->lockFile,json_encode($this->contents,true),$msg);
            }

        }
        if(file_exists($this->backup)) {
            unlink($this->backup);
        }
    }

    /**
     * 直接用命令方式读取
     *
     * @param $command
     * @param $data
     */
    protected function readCli_byCmd($command,$file)
    {
        $result = exec($command,$output,$return_code);
        if(strpos($command,'sc_mf_all') !== false)
        {
            usleep(100*1000);
        }
        else{
            usleep(50*1000);
        }


        //if($return_code == 0) {
        $fileInfo = file_get_contents($file, true);
        return $fileInfo;
        //} else {
        //    return '';
        //}

    }

    protected function readCli_byCmd_noRet($command,$file)
    {
        //1.读取当前状态的配置信息

        $result = exec($command,$output,$return_code);
        usleep(500*1000);
        if(!file_exists($file))
        {
            return "";
        }

        $fileInfo = file_get_contents($file, true);
        return $fileInfo;

    }

    /**
     * 直接用命令方式读取
     *
     * @param $command
     * @param $data
     */
    protected function writeJson($command,$data,$file)
    {
        //1.读取当前状态的配置信息
        $this->contents = [
            'user'      => $this->loginUserInfo,
            'params'    => $this->_params,
            'commands'  => $command,
            'api'       => $this->_request->getUri()->getPath(),
            'process'   => [],
        ];
        //2.备份读取数据 -> _new_.json
        $this->backup = '/tmp/'.str_replace("_", "_new_", base64_encode($file).".json");
        if (!copy($file, $this->backup)) {
                $msg = '备份文件失败：' . $this->backup;
                array_push($this->contents['process'], ['desc' => $msg]);
                $this->afterHandle($this->lockFile, json_encode($this->contents, true), $msg);
        } else {
                array_push($this->contents['process'], ['desc' => '备份文件成功：' . $this->backup]);
        }

        $logName = $this->log_dir.date('Y-m-d',time()).'.log';
        if(!file_exists($logName)) {
            if(!is_dir($this->log_dir)) {
                mkdir('/tmp/operator/gui/',0777,true);
            }
            file_put_contents($logName,'------ init log | '.$logName . ' -------------'.PHP_EOL,8);
            exec('chown www-data:root '.$logName,$output,$return_code);
        }

        $this->lockFile = fopen($logName,'a+');

        //3.修改内容
        $fileInfo = file_get_contents($this->backup, true);
        $fileInfo = json_decode($fileInfo, true);

        $isChange = 0;
        foreach ($data as $field => $value) {
            if (isset($fileInfo[$field])) {
                $isChange = 1;
                if(is_array($fileInfo[$field])) {
                    $fileInfo[$field] = array_merge($fileInfo[$field],$value);

                        /*
                        if(isset($fileInfo[$field][$i])) {
                            $fileInfo[$field][$i] = array_merge($fileInfo[$field][$i],$value);
                            return $fileInfo[$field][$i];
                        }
                        */

                } else {
                    $fileInfo[$field] = $value;
                }
            }
        }

        if($isChange > 0) {
            array_push($this->contents['process'],['desc' => '文件 '.$this->backup.' 值被更改']);
            //保存修改内容
            if(!file_put_contents($file,json_encode($fileInfo,true))) {
                unlink($this->backup);
                $msg = '修改备份文件内容失败：'.$this->backup;
                array_push($this->contents['process'],['desc' => $msg]);
                $this->afterHandle($this->lockFile,json_encode($this->contents,true),$msg);
            }

        } else {
            array_push($this->contents['process'],['desc' => '文件 '.$this->backup.' 值没有发生更改，不调用CLI']);
            $msg =  '值没有发生更改，不调用CLI';
            $this->afterHandle($this->lockFile,json_encode($this->contents,true),$msg);
        }
    }

    protected function writeJson2($command,$data,$file)
    {
        //1.读取当前状态的配置信息
        $this->contents = [
            'user'      => $this->loginUserInfo,
            'params'    => $this->_params,
            'commands'  => $command,
            'api'       => $this->_request->getUri()->getPath(),
            'process'   => [],
        ];
        //2.备份读取数据 -> _new_.json
        $this->backup = '/tmp/'.str_replace("_", "_new_", base64_encode($file).".json");
        if (!copy($file, $this->backup)) {
            $msg = '备份文件失败：' . $this->backup;
            array_push($this->contents['process'], ['desc' => $msg]);
            $this->afterHandle($this->lockFile, json_encode($this->contents, true), $msg);
        } else {
            array_push($this->contents['process'], ['desc' => '备份文件成功：' . $this->backup]);
        }

        $logName = $this->log_dir.date('Y-m-d',time()).'.log';
        if(!file_exists($logName)) {
            if(!is_dir($this->log_dir)) {
                mkdir('/tmp/operator/gui/',0777,true);
            }
            file_put_contents($logName,'------ init log | '.$logName . ' -------------'.PHP_EOL,8);
            exec('chown www-data:root '.$logName,$output,$return_code);
        }

        $this->lockFile = fopen($logName,'a+');

        //3.修改内容
        $fileInfo = file_get_contents($this->backup, true);
        $fileInfo = json_decode($fileInfo, true);

        $isChange = 0;
        foreach ($data as $field => $value) {
            if (isset($fileInfo[$field])) {
                $isChange = 1;
                if(is_array($fileInfo[$field])) {
                    $fileInfo[$field] = array_replace($fileInfo[$field],$value);
                    //$fileInfo[$field] = array_merge($fileInfo[$field],$value);

                } else {
                    $fileInfo[$field] = $value;
                }
            }
        }

        if($isChange > 0) {
            array_push($this->contents['process'],['desc' => '文件 '.$this->backup.' 值被更改']);
            //保存修改内容
            if(!file_put_contents($file,json_encode($fileInfo,true))) {
                unlink($this->backup);
                $msg = '修改备份文件内容失败：'.$this->backup;
                array_push($this->contents['process'],['desc' => $msg]);
                $this->afterHandle($this->lockFile,json_encode($this->contents,true),$msg);
            }

        } else {
            array_push($this->contents['process'],['desc' => '文件 '.$this->backup.' 值没有发生更改，不调用CLI']);
            $msg =  '值没有发生更改，不调用CLI';
            $this->afterHandle($this->lockFile,json_encode($this->contents,true),$msg);
        }
    }

    /**
     * 直接用命令方式写入
     *
     * @param $command
     * @param $data
     */
    protected function writeJsonFile($data,$file)
    {

        //2.备份读取数据 -> _new_.json
        $this->backup = '/tmp/'.str_replace("_", "_new_", base64_encode($file).".json");
        if (!copy($file, $this->backup)) {

        }

        if(!file_put_contents($file,json_encode($data,true))) {
            unlink($this->backup);
        }
    }
    /**
     * 重写权限
     *
     * @param $command
     */
    protected function reAuth()
    {
        $resp = exec('chown -R www-data:root /home/bird/data');
        $resp = exec('chown www-data:root /home/bird/data/admin.sqlite');
        $resp = exec('chown -R www-data:root /tmp/');
        $resp = exec('chown -R www-data:root /var/www/backend/');
        $resp = exec('chown www-data:root /tmp/config.sqlite');
    }
    /**
     * 直接用命令方式读取
     *
     * @param $command
     * @param $data
     */
    protected function readfile($file)
    {
        $fileInfo = file_get_contents($file, true);
        return $fileInfo;

    }

    /**
     * 后处理器
     *
     * @param $file
     * @param $content
     * @param $errMsg
     * @param $unfile
     * @return mixed
     */
    private function afterHandle($file,$content,$errMsg,$unfile = null)
    {
        if($unfile) {
            if(file_exists($unfile)) {
                unlink($unfile);
            }
        }
        fwrite($file,$content . PHP_EOL);
        flock($file,LOCK_UN);//unlock
        fclose($file);
        return $this->error($errMsg);
    }



}
