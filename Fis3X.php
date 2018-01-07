<?php

class Fis3X
{
    /**
     * 全局命名空间名称
     * @var string
     */
    const NAMESPACE_GLOBAL = 'common';

    /**
     * 资源映射表文件名称
     * @var string
     */
    const MAP_FILE = 'map.json';

    /**
     * 三种占位符名称定义
     * @var string
     */
    const PLACEHOLDER_NAME_FRAMEWORK    = 'framework';
    const PLACEHOLDER_NAME_SCRIPT       = 'script';
    const PLACEHOLDER_NAME_STYLE        = 'style';

    /**
     * 三种占位符定义
     * @var string
     */
    const PLACEHOLDER_FRAMEWORK    = '<!--[FIS_FRAMEWORK_HOOK]-->';
    const PLACEHOLDER_SCRIPT        = '<!--[FIS_SCRIPT_HOOK]-->';
    const PLACEHOLDER_STYLE         = '<!--[FIS_STYLE_HOOK]-->';
    /**
     * 资源映射表
     * @static
     * @var array
     */
    protected static $map = [];

    /**
     * 依赖集合
     * @var array
     */
    protected static $collection = [
        'css' => [],
        'js' => []
    ];

    /**
     * 已加载的资源数据
     *
     * 此数组的具体格式如下：
     * [
     *      [key => value],
     *      [key => value]
     * ]
     * key为资源id值
     * value值为资源id对应的映射表中的uri值
     *
     * @var array
     */
    protected static $loaded = [];

    /**
     * 当前使用的前端框架
     * @var string
     */
    protected static $framework = null;

    /**
     * 获取文件路径
     *
     * 主要用于在smarty增加fis3的Template Resources时，增加对模板绝对路径的处理
     * @static
     * @param string $resourceId 资源id
     * @param \Smarty $smarty smarty对象
     * @return string|false 成功返回文件路径，否则返回false
     */
    public static function getFilePath($resourceId, $smarty)
    {
        $filePath = static::getUri($resourceId, $smarty);
        if (false === $filePath) {
            return false;
        }
        return static::directoryFormat($filePath);
    }

    /**
     * 根据资源id返回资源的依赖列表
     *
     * @static
     * @param string $resourceId 资源id
     * @param \Smarty $smarty smarty对象
     * @return array|false 成功返回资源依赖列表信息，否则返回false
     */
    public static function getDeps($resourceId, $smarty)
    {
        $ret = static::parseResourceid($resourceId);
        if (false === $ret) {
            return false;
        }
        list($namespace, $location) = $ret;

        $map = static::getMapByNamespace($namespace, $smarty);
        if (false === $map) {
            return false;
        }

        if (isset($map['res'][$resourceId])) {
            $arrRes = &$map['res'][$resourceId];
            if (!array_key_exists('fis_debug', $_GET) && isset($arrRes['pkg'])) {
                $arrPkg = &$map['pkg'][$arrRes['pkg']];
                return isset($arrPkg['deps']) ? $arrPkg['deps'] : [];
            } else {
                return isset($arrRes['deps']) ? $arrRes['deps'] : [];
            }
        } else {
            //$message = "Miss resourceId({$resourceId}) data in the map.(getDeps)";
            //static::triggerError($message, E_USER_ERROR);
            return false;
        }
    }

    /**
     * 根据资源id返回资源的uri
     * @static
     * @param string $resourceId 资源id
     * @param \Smarty $smarty smarty对象
     * @return string|false 成功返回资源uri信息，否则返回false
     */
    public static function getUri($resourceId, $smarty)
    {
        $ret = static::parseResourceid($resourceId);
        if (false === $ret) {
            return false;
        }
        list($namespace, $location) = $ret;

        $map = static::getMapByNamespace($namespace, $smarty);
        if (false === $map) {
            return false;
        }
        if (isset($map['res'][$resourceId])) {
            $arrRes = &$map['res'][$resourceId];
            if (!array_key_exists('fis_debug', $_GET) && isset($arrRes['pkg'])) {
                $arrPkg = &$map['pkg'][$arrRes['pkg']];
                return $arrPkg['uri'];
            } else {
                return $arrRes['uri'];
            }
        } else {
            //$message = "Miss resourceId({$resourceId}) data in the map.";
            //static::triggerError($message, E_USER_ERROR);
            return false;
        }
    }

    /**
     * 在模板中输出点位符（现支持三种，framework, script, style）
     * @static
     * @param string $placeholder 点位符名称
     * @return string
     */
    public static function placeHolder($placeholder)
    {
        $text = '';
        switch (strtolower(trim($placeholder))) {
            case static::PLACEHOLDER_NAME_FRAMEWORK:
                $text = static::PLACEHOLDER_FRAMEWORK;
                break;
            case static::PLACEHOLDER_NAME_SCRIPT:
                $text = static::PLACEHOLDER_SCRIPT;
                break;
            case static::PLACEHOLDER_NAME_STYLE:
                $text = static::PLACEHOLDER_STYLE;
                break;
            default:
                break;
        }
        return $text;
    }

    public static function load($resourceId, $smarty)
    {
        $ret = static::parseResourceid($resourceId);
        if (false === $ret) {
            return false;
        }
        list($namespace, $location) = $ret;

        $map = static::getMapByNamespace($namespace, $smarty);
        if (false === $map) {
            return false;
        }
        //TODO 若已加载，为什么要返回值，这个值是什么
        if (isset(static::$loaded[$resourceId])) {
            return static::$loaded[$resourceId];
        }
        //$arrPkg = null;
        //$arrPkgHas = array();
        if(isset($map['res'][$resourceId])) {
            $arrRes = &$map['res'][$resourceId];
            static::$loaded[$resourceId] = $arrRes['uri'];

            static::loadDeps($arrRes, $smarty);
            static::$collection[$arrRes['type']][] = $arrRes['uri'];
        } else {
            //$message = "Miss resourceId({$resourceId}) data in the map.(load)";
            //static::triggerError($message, E_USER_ERROR);
            return '';
        }
    }
    /**
     * 设置框架信息
     * @static
     * @param string $name 框架名称
     * @return void
     */
    public static function setFramework($name)
    {
        static::$framework = trim($name);
    }

    public static function render($content)
    {
        //render for framework
        $pos = strpos($content, static::PLACEHOLDER_FRAMEWORK);
        if(false !== $pos){
            $content = substr_replace(
                $content,
                static::renderPlaceHoder(static::PLACEHOLDER_NAME_FRAMEWORK),
                $pos,
                strlen(static::PLACEHOLDER_FRAMEWORK)
            );
        }
        //render for script
        $pos = strpos($content, static::PLACEHOLDER_SCRIPT);
        if (false !== $pos){
            //$jsContent .= self::render('js') . self::renderScriptPool();
            //$strContent = substr_replace($strContent, $jsContent, $jsIntPos, strlen(self::JS_SCRIPT_HOOK));
            $content = substr_replace(
                $content,
                static::renderPlaceHoder(static::PLACEHOLDER_NAME_SCRIPT),
                $pos,
                strlen(static::PLACEHOLDER_SCRIPT)
            );
        }
        //render for style
        $pos = strpos($content, static::PLACEHOLDER_STYLE);
        if (false !== $pos){
            $content = substr_replace(
                $content,
                static::renderPlaceHoder(static::PLACEHOLDER_NAME_STYLE),
                $pos,
                strlen(static::PLACEHOLDER_STYLE)
            );
        }

        static::reset();

        return $content;
    }
    /**
     * 解析资源id格式到命名空间和定位
     * @static
     *
     * @param string $resourceId [引]资源id
     * @return array|false 成功返回解析的项数据,否则返回false
     */
    protected static function parseResourceid(&$resourceId)
    {
        if (!static::resourceIdCheck($resourceId, __LINE__)) {
            return false;
        }
        $arr = explode(':', $resourceId, 2) + [null, null];
        $arr =  array_map('trim', $arr);

        if (strlen($arr[0]) <= 0) {
            $arr[0] = static::NAMESPACE_GLOBAL;
            /*$message =
                "Resource namespace for '$resourceId' Can not be empty, reset to " .
                static::NAMESPACE_GLOBAL .
                '.'
            ;
            static::triggerError($message, E_USER_WARNING);*/
        } else if (strlen($arr[1]) <= 0) {
            $arr[1] = $arr[0];
            $arr[0] = static::NAMESPACE_GLOBAL;
            /*$message =
                "Invalid resource namespace for '$resourceId', reset to " .
                static::NAMESPACE_GLOBAL .
                '.'
            ;
            static::triggerError($message, E_USER_WARNING);*/
        }

        $arr = array_map(function($val) {
            $tmp = str_replace([
                '/',
                '\\'
            ], [
                '/',
                '/'
            ], $val);
            return $tmp;
        }, $arr);

        $resourceId = implode(':', $arr);
        return $arr;
    }

    /**
     * 对资源id进行合法性检查
     * @static
     * @param string $resourceId 资源id
     * @param int $line 错误行号
     * @return bool 成功返回true,否则返回false
     */
    protected static function resourceIdCheck(&$resourceId, $line = 0)
    {
        $resourceId = trim($resourceId);
        if (strlen($resourceId) <= 0) {
            $message = 'Invalid resource id.';
            if (is_int($line) && $line > 0 ) {
                $message .= "(LINE: $line)";
            }
            static::triggerError($message, E_USER_ERROR);
            return false;
        }
        return true;
    }

    /**
     * 从资源映射表文件中加载资源映射表数据
     * @static
     *
     * @param string $namespace 命名空间
     * @param \Smarty $smarty smarty对象
     * @return bool 成功返回true,否则返回false
     */
    protected static function registerMap($namespace, $smarty)
    {
        $mapFile = "{$namespace}-" . static::MAP_FILE;

        $arrConfigDir = $smarty->getConfigDir();
        foreach ($arrConfigDir as $dir) {
            $path = preg_replace('/[\\/\\\\]+/', '/', $dir . '/' . $mapFile);
            if (is_file($path)) {
                $json = @file_get_contents($path);
                if (false === $json) {
                    $message = "Can't read map file '{$path}' .";
                    static::triggerError($message, E_USER_WARNING);
                    return false;
                }
                $json = json_decode($json, true);
                if (is_null($json)) {
                    $message = "Can't decode the map file '{$path}' .";
                    static::triggerError($message, E_USER_WARNING);
                    return false;
                }
                static::$map[$namespace] = $json;
                return true;
            }
        }
        $message = "Missing map file '{$mapFile}' .";
        static::triggerError($message, E_USER_WARNING);
        return false;
    }

    /**
     * 根据指定命名空间，获取资源映射表信息
     * @static
     * @param string $namespace 命名空间
     * @param \Smarty $smarty smarty对象
     * @return array|null 正常返回资源映射表数组，否则返回null
     */
    protected static function getMapByNamespace($namespace, $smarty)
    {
        if (isset(static::$map[$namespace]) || static::registerMap($namespace, $smarty)) {
            return static::$map[$namespace];
        }
        return null;
    }

    protected static function renderPlaceHoder($name)
    {
        switch($name) {
            case static::PLACEHOLDER_NAME_FRAMEWORK:
                return static::renderFramework();
                break;
            case static::PLACEHOLDER_NAME_SCRIPT:
                return static::renderScript();
                breka;
            case static::PLACEHOLDER_NAME_STYLE:
                return static::renderStyle();
                break;
            default:
                return '';
                break;
        }
    }

    /**
     * 渲染界面框架的HTML代码
     * @static
     * @return string
     */
    protected static function renderFramework()
    {
        $html = '';
        //$resourceMap = self::getResourceMap();
        //$loadModJs = (self::$framework && (isset(self::$arrStaticCollection['js']) || $resourceMap));
        //require.resourceMap要在mod.js加载以后执行
        //只要设置也框架，一定加载框架
        if (static::$framework) {
            $html .= '<script type="text/javascript" src="' . static::$framework . '"></script>' . PHP_EOL;
        }
        return $html;
    }

    /**
     * 渲染界面STYLE的HTML代码
     * @static
     * @return string
     */
    protected static function renderStyle()
    {
        $html = '';
        if (isset(static::$collection['css']) && count(static::$collection['css']) > 0){
            $html  = '<link rel="stylesheet" type="text/css" href="';
            $html .= implode('"/><link rel="stylesheet" type="text/css" href="', static::$collection['css']);
            $html .= '"/>';
        }
        return $html;
    }

    /**
     * 渲染界面script的HTML代码
     * @static
     * @return string
     */
    protected static function renderScript()
    {
        $html = '';
        if (isset(static::$collection['js']) && count(static::$collection['js']) > 0) {
            $html = '<script type="text/javascript">';
            $html .= 'seajs.use([\'' . implode('\',\'', static::$collection['js']) . '\']);';
            $html .= '</script>';
        }
        return $html;
    }

    /**
     * 分析资源节点的依赖
     * @static
     * @param array $arrRes  资源节点详细信息
     * @param Object $smarty  smarty对象
     * @return void
     */
    protected static function loadDeps($resNode, $smarty) {
        //require.async
        /*if (isset($arrRes['extras']) && isset($arrRes['extras']['async'])) {
            foreach ($arrRes['extras']['async'] as $uri) {
                self::load($uri, $smarty, true);
            }
        }*/
        if (isset($resNode['deps'])) {
            foreach ($resNode['deps'] as $dep) {
                static::load($dep, $smarty);
            }
        }
    }


    /**
     * 重置所有数据
     * @static
     *
     * @return void
     */
    protected static function reset()
    {
        static::$map = [];
        static::$collection = [];
        static::$loaded = [];
        static::$framework = null;
    }

    /**
     * 触发PHP错误提示
     * @static
     * @param $message      错误信息
     * @param int $level    错误等级
     * @return void
     */
    protected static function triggerError($message, $level)
    {
        trigger_error($message, $level);
    }

    /**
     * 格式化路径分隔符
     * @static
     * @param string $dir 路径
     * @return string
     */
    protected static function directoryFormat($dir) {
        return str_replace([
            '/',
            '\\'
        ], [
            DIRECTORY_SEPARATOR,
            DIRECTORY_SEPARATOR
        ], $dir);
    }
}
