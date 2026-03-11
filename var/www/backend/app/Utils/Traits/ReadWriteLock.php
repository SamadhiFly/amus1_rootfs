<?php
namespace App\Utils\Traits;

/**
 * Trait ReadWriteLock
 * @package App\Utils\Traits
 */
trait ReadWriteLock
{

    /**
     * Read lock  | default is false
     *
     * @var bool
     */
    protected $readLock = false;


    /**
     * write lock  | default is false
     *
     * @var bool
     */
    protected $writeLock = false;


    /**
     * read write lock function
     */
    protected function RwLock()
    {
        $lock = true;

        if($this->readLock) {
            $lock = $this->read();
        }

        if($this->writeLock) {
            $lock = $this->write();
        }

        if(!$lock) {
            return $this->error('Someone is working on this feature, please do it later.');
        }
    }


    /**
     * read file lock
     */
    private function read()
    {
        $file = fopen('/tmp/gui-read-lock.txt','w+');

        //lock
        if(flock($file,LOCK_EX|LOCK_NB)) {

            //run code

            //unlock
            flock($file,LOCK_UN);//unlock
        } else {

            return false;
        }
        fclose($file);//close file
        return true;
    }


    /**
     * write file lock
     */
    private function write()
    {
        $file = fopen('/tmp/gui-write-lock.txt','w+');

        $lr = flock($file,LOCK_EX);

        if($lr < 1) {
            exit('lock fail');
            fwrite($file,"Write lock fail  : ");
            return false;
        }

        //unlock
        flock($file,LOCK_UN);//unlock
        fclose($file);//close file
        return true;
    }


}