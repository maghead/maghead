<?php
namespace Maghead\Exception;
use RuntimeException;
use Exception;
use Maghead\BaseModel;

class QueryException extends RuntimeException
{
    protected $debugInfo = array();

    protected $record;

    public function __construct($msg, BaseModel $record, Exception $previous = null, $debugInfo = array())
    {
        parent::__construct($msg, 0, $previous);
        $this->debugInfo = $debugInfo;
        $this->record = $record;
    }

    public function __debugInfo()
    {
        return [
            'message' => $this->getMessage(),
            'record' => get_class($this->record),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'trace' => $this->getTrace(),
            'previous_trace' => $this->getPrevious()->getTrace(),
            'previous_message' => $this->getPrevious()->getMessage(),
            'debug' => $this->debugInfo,
        ];
    }

    public function toArray()
    {
        return [
            'message' => $this->getMessage(),
            'record' => get_class($this->record),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'trace' => $this->getTrace(),
            'previous' => $this->getPrevious(),
            'debug' => $this->debugInfo,
        ];
    }
}
