<?php
namespace App\Utils\Traits;

use Overtrue\Validation\Translator;
use Overtrue\Validation\Factory as ValidatorFactory;

/**
 * Trait ValidatorTools
 * @package App\Utils\Traits
 */
trait ValidatorTools
{

    /**
     * todo sustainable....
     *
     * @param array $args
     * @param array $rules
     * @param array $messages
     * @return bool
     */
    protected function _validate(array $args, array $rules, array $messages = [])
    {

        $factory = new ValidatorFactory(new Translator($messages));

        $validator = $factory->make($args, $rules);

        if (!$validator->passes()) {
            return $this->error($validator->messages()->first());
        }
        return true;
    }

}