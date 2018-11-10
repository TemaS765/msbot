<?php
/**
 * Created by PhpStorm.
 * User: Aracon
 * Date: 23.02.16
 * Time: 12:10
 */

namespace B24Process;


class ControllerResult
{
    private $template_name;
    private $data;

    public function __construct($template_name, array $data)
    {
        $this->template_name = $template_name;
        $this->data = $data;
    }

    public function getTemplateName() {
        return $this->template_name;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setTemplateName($template_name)
    {
        $this->template_name = $template_name;
    }

    public function setData($data)
    {
        $this->data = $data;
    }
}