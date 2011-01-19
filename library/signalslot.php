<?php

interface ISignalSlot
{
//    public function __construct();
    public function connect($signal, $context, $slot, $config = array());
    public function disconnect($signal, $context, $slot);
    public function emit($signal, $args = null);
//    protected function setup();
}

// omitted other methods for now
class SignalSlot implements ISignalSlot
{
    protected $signals = array();

    public function __construct()
    {
        $this->setup();
    }
    
    protected function setup()
    {
        $ref   = new \ReflectionClass($this);
        $const = $ref->getConstants();
        if (!empty($const))
        {
            foreach ($const as $key => $val)
            {
                if (substr($key, 0, 7) === 'SIGNAL_')
                {
                    $this->signals[$val] = array();
                    if (!method_exists($this, $val))
                    {
                        throw new \Exception($val . '() method does not exist.');
                    }
                }
            }
        }
    }
    

    public function connect($signal, $context, $slot, $config = array())
    {
        if (!isset($this->signals[$signal]))  {
            throw new \Exception ($signal . ' is not declared');
        }      

        $this->signals[$signal][] = array('context' => $context,
                                          'slot'    => $slot,
                                          'config'  => $config);
    }
   
    public function disconnect($signal, $context, $slot)
    {
        if (!isset($this->signals[$signal]) || empty($this->signals[$signal]))  {
            return; // throw exception if signal is not set??
        }
        $def = array('context' => $context, 'slot' => $slot);
        foreach ($this->signals[$signal] as $id => $receiver)
        {
            unset($receiver['config']);
            if ($receiver === $def)
            {
                unset($this->signals[$signal][$id]);
                return true;
            }
        }
        return false;
    }

    public function emit($signal, $args = null)
    {
        if (!isset($this->signals[$signal]) || empty($this->signals[$signal]))  {
            return; // throw exception if signal is not set?
        }
        $return = null;
        $args = array_slice(func_get_args(), 1);
        foreach ($this->signals[$signal] as $receiver)  {
            $context = $receiver['context'];
            $method  = $receiver['slot'];
            $config  = $receiver['config'];
            if (is_string($context)) {
                $context = !empty($config) ? new $context($config) : new $context();
            }
            switch (count($args)) {
            case 0:
                $return = $context->{$method}();
                break;
            case 1:
                $return = $context->{$method}($args[0]);
                break;
            case 2:
                $return = $context->{$method}($args[0], $args[1]);
                break;
            case 3:
                $return = $context->{$method}($args[0], $args[1], $args[2]);
                break;
            case 4:
                $return = $context->{$method}($args[0], $args[1], $args[2], $args[3]);
                break;
            case 5:
                $return = $context->{$method}($args[0], $args[1], $args[2], $args[3], $args[4]);
                break;
            case 6:
                $return = $context->{$method}($args[0], $args[1], $args[2], $args[3], $args[4], $args[5]);
                break;
            default:
                return false;
            }
        }
        return $return;
    }
}