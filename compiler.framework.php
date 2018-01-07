<?php

function smarty_compiler_framework($arrParams,  $smarty) {
    $name = isset($arrParams['name']) ? $arrParams['name'] : '';

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
echo Fis3X::setFramework(Fis3X::getUri({$name}, \$_smarty_tpl));

\$_smarty_tpl->registerFilter('output', array('Fis3X', 'render'));
?>
CODE;
    return $code;
}