<?php

class Smarty_Resource_Fis3 extends Smarty_Internal_Resource_File
{
    /**
     * 返回通过fis3资源定位的文件路径
     * @param Smarty_Template_Source $source
     * @param Smarty_Internal_Template|null $_template
     * @return string
     */
    protected function buildFilepath(Smarty_Template_Source $source, Smarty_Internal_Template $_template = null)
    {
        if (!class_exists('Fis3X', false)) {
            include_once __DIR__ . '/Fis3X.php';
        }

        $name = Fis3X::getFilePath($source->name, $source->smarty);
        if (false === $name) {
            throw new SmartyException("Miss file path for '{$source->name}'");
        }
        $source->name = $name;

        return parent::buildFilepath($source, $_template);
    }
}