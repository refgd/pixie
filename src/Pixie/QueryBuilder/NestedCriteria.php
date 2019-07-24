<?php namespace Pixie\QueryBuilder;

class NestedCriteria extends QueryBuilderHandler
{
    /**
     * @param        $key
     * @param null   $operator
     * @param null   $value
     * @param string $joiner
     *
     * @return $this
     */
    protected function whereHandler($key, $operator = null, $value = null, $joiner = 'AND')
    {
        if(is_array($value)){
            if($operator == 'NOT IN' || $operator == 'NOT'){
                $operator = 'NOT IN';
            }else{
                $operator = 'IN';
            }
        }
        
        $key = $this->addTablePrefix($key);
        $this->statements['criteria'][] = compact('key', 'operator', 'value', 'joiner');
        return $this;
    }
}
