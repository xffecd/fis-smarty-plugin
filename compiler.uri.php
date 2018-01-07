<?php

function smarty_compiler_uri($arrParams,  $smarty){
    $name = isset($arrParams['name']) ? trim($arrParams['name']) : '';

    $classPath = preg_replace(
        '/[\\/\\\\]+/',
        DIRECTORY_SEPARATOR,
        __DIR__ . '/Fis3X.php'
    );

    $code = <<<CODE
<?php
if (!class_exists('Fis3X', false)) {
    include_once '{$classPath}';
}
echo Fis3X::getUri({$name}, \$_smarty_tpl->smarty);
?>
CODE;

    return $code;
}
