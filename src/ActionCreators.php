<?php
namespace Maghead;


trait ActionCreators
{
    public function asCreateAction(array $args = array(), array $options = array())
    {
        // the create action requires empty args
        return $this->newAction('Create', $args, $options);
    }

    public function asUpdateAction(array $args = array(), array $options = array())
    {
        // should only update the defined fields
        return $this->newAction('Update', $args, $options);
    }

    public function asDeleteAction(array $args = array(), array $options = array())
    {
        $pk = static::PRIMARY_KEY;
        if ($this->hasKey()) {
            $args[$pk] = $this->getKey();
        }
        $data = $this->getData();
        return $this->newAction('Delete', array_merge($data, $args), $options);
    }

    /**
     * Create an action from existing record object.
     *
     * @param string $type 'create','update','delete'
     */
    public function newAction($type, array $args = array(), $options = array())
    {
        $class = get_class($this);
        $actionClass = \ActionKit\RecordAction\BaseRecordAction::createCRUDClass($class, $type);
        $options['record'] = $this;

        return new $actionClass($args, $options);
    }

    public function getRecordActionClass($type)
    {
        $class = get_class($this);
        return \ActionKit\RecordAction\BaseRecordAction::createCRUDClass($class, $type);
    }
}
