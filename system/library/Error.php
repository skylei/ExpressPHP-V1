<?php

/**
 * Error类
 * @author 刘健 <code.liu@qq.com>
 */

namespace sys;

use sys\response\SysView;

class Error
{

    private static $processed;

    // 注册异常处理
    public static function register()
    {
        error_reporting(E_ALL);
        set_error_handler([__CLASS__, 'appError']);
        set_exception_handler([__CLASS__, 'appException']);
        register_shutdown_function([__CLASS__, 'appShutdown']);
    }

    // Error Handler
    public static function appError($errno, $errstr, $errfile = '', $errline = 0, $errcontext = [])
    {
        throw new \sys\exception\ErrorException($errno, $errstr, $errfile, $errline);
    }

    // Error Handler
    public static function appShutdown()
    {
        if ($error = error_get_last()) {
            self::appException(new \sys\exception\ErrorException($error['type'], $error['message'], $error['file'], $error['line']));
        }
    }

    // Exception Handler
    public static function appException($e)
    {
        // 获取配置
        $appDebug = Config::get('config.app_debug');
        // 清除无法接管的php系统语法错误
        ob_clean();
        // 生成视图
        $sysView  = new SysView('template.exception');
        $response = Response::create($sysView);
        $response->code(500);
        if ($e instanceof \sys\exception\HttpException) {
            $response->code(404);
            $sysView->assign('message', $e->getStatusCode() . ' / ' . $e->getMessage());
        } else if (!$appDebug) {
            $sysView->assign('message', '500 / 服务器内部错误');
        } else if ($e instanceof \sys\exception\ErrorException) {
            $sysView->assign('message', '系统错误 / ' . $e->getMessage());
        } else if ($e instanceof \sys\exception\RouteException) {
            $response->code(404);
            $sysView->assign('message', '路由错误 / ' . $e->getMessage() . ' / ' . $e->getLocation());
        } else if ($e instanceof \sys\exception\ConfigException) {
            $sysView->assign('message', '配置错误 / ' . $e->getMessage() . ' / ' . $e->getLocation());
        } else if ($e instanceof \sys\exception\ViewException) {
            $sysView->assign('message', '视图错误 / ' . $e->getMessage() . ' / ' . $e->getLocation());
        } else {
            $sysView->assign('message', '未定义错误 / ' . $e->getMessage());
        }
        if ($appDebug) {
            $sysView->assign('file', $e->getFile());
            $sysView->assign('line', $e->getLine());
            $sysView->assign('trace', $e->getTraceAsString());
        }
        $response->send();
    }

}
