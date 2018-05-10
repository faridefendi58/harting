<?php

namespace Components;

class Tool
{
    protected $_basePath;

    public function __construct($_basePath = null)
    {
        $this->_basePath = $_basePath;
    }
    
    public function get_css($data, $eregs = null)
    {
        if (!file_exists($this->_basePath . $data['path']))
            return false;
        $result = file_get_contents($this->_basePath . $data['path']);
        if ($result) {
            if ($eregs) {
                if (!is_array($eregs['patern'])) {
                    $pattern = $eregs['patern'];
                    $patterns = "/" . preg_replace(['/\//'], ['\/'], $pattern) . "/";
                    $replacements = $eregs['replacement'];
                    $result = preg_replace([$patterns], [$replacements], $result);
                } else {
                    $patterns = [];
                    foreach ($eregs['patern'] as $i => $pat) {
                        $new_pat = "/" . preg_replace(['/\//'], ['\/'], $pat) . "/";
                        $patterns[$i] = $new_pat;
                    }
                    $result = preg_replace($patterns, $eregs['replacement'], $result);
                }
            }
            
            return '<style>' . $result . '</style>';
        } else {
            return false;
        }
    }

    public function get_js($data)
    {
        if (!file_exists($this->_basePath . $data['path']))
            return false;
        $result = file_get_contents($this->_basePath . $data['path']);
        if ($result) {
            return '<script type="text/javascript">' . $result . '</script>';
        } else {
            return false;
        }

    }

    /**
     * Initiated new class name from view
     * ex : {% set hpmodel = App.tool.get_model('ExtensionsModel.HostingPlanModel') %}
     * @param $path
     * @return mixed
     */
    public function get_model($path)
    {
        if (strpos($path, '.') !== false) {
            $class_name = "\\" . str_replace(".", "\\", $path);
        } else {
            $class_name = "\\" . $path;
        }

        return new $class_name();
    }
}