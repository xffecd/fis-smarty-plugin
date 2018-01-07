<?php
function smarty_compiler_static_deps($arrParams, $smarty)
{
    $relative = str_replace($smarty->_joined_template_dir, '', $smarty->_current_file);
    if (false === $relative) {
        return '';
    }
    $pos = false;
    foreach (['page', 'widget'] as $feature) {
        $text = $feature . DIRECTORY_SEPARATOR;
        $pos = strpos($relative, $text);
        if (false !== $pos) {
            break;
        }

        $text  = DIRECTORY_SEPARATOR . $text;
        $pos = strpos($relative, $text, 1);
        if (false !== $pos) {
            break;
        }
    }
    if (false === $pos) {
        return '';
    }

    if (!class_exists('Fis3X', false)) {
        include_once __DIR__ . '/Fis3X.php';
    }

    if (0 === $pos) {
        $resourceId = Fis3X::NAMESPACE_GLOBAL . ":$relative";
    } else {
        $resourceId = substr_replace($relative, ':', $pos-1, 1);
    }
    $deps = Fis3X::getDeps($resourceId, $smarty);
    if (false === $deps) {
        throw new SmartyException("Failed to get deps for resourceId({$resourceId})");
    }
    //

    //TODO 将静态依赖加载到当前的依赖列表中
    foreach ($deps as $dep) {
        Fis3X::load($dep, $smarty);
    }
}