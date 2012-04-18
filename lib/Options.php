<?php

class Options
{
    static public function getOptions()
    {
        require dirname(__FILE__).'/Argument.php';
        require dirname(__FILE__).'/Symfony.php';
        require dirname(__FILE__).'/MultiConfig.php';
        require dirname(__FILE__).'/Color.php';
        require dirname(__FILE__).'/FileTools.php';
        require dirname(__FILE__).'/PosixTools.php';

        $project_path = Symfony::getProjectPath();
        $options = MultiConfig::get();

        $options['port'] = intval(Argument::get('p', 'port', 4042));
        $options['bind'] = Argument::get('A', 'all', false)
                        ? false
                        : Argument::get('b', 'bind', '127.0.0.1');
        $options['project_path'] = $project_path;
        $options['config_dir'] = $project_path.'/symfttpd';
        $options['config_file'] = $options['config_dir'].'/lighttpd.conf';
        $options['log_dir'] = $project_path.'/symfttpd/log';
        // hack: .sf files are not removed by symfony cc
        $options['pidfile'] = $options['config_dir'].'/.sf';
        $options['restartfile'] = $options['config_dir'].'/.symfttpd_restart';
        $options['tail'] = Argument::get('t', 'tail', false);
        $options['fork'] = !Argument::get('s', 'single-process', false);
        if ($options['fork'] && !function_exists('pcntl_fork'))
        {
        log_message('Warning: No fork() support.'
            . 'symfttpd will run in single-process mode.');
        $options['fork'] = false;
        }
        $options['color'] = !Argument::get('C', 'no-color', false) && posix_isatty(STDOUT);
        if ($options['color'])
        {
        Color::enable();
        }

        if (Argument::get('K', 'kill', false))
        {
        if (file_exists($options['restartfile']))
        {
            unlink($options['restartfile']);
        }
        exit(!PosixTools::killPid($options['pidfile']));
        }

        FileTools::mkdirs($options['config_dir']);
        FileTools::mkdirs($options['log_dir']);

        PosixTools::setCustomPath($options['custom_path']);
        try
        {
        if (empty($options['lighttpd_cmd']))
        {
            $options['lighttpd_cmd'] = PosixTools::which('lighttpd');
        }

        if (empty($options['php_cgi_cmd']))
        {
            $options['php_cgi_cmd'] = PosixTools::which('php-cgi');
        }

        if (empty($options['php_cmd']))
        {
            $options['php_cmd'] = PosixTools::which('php');
        }
        }
        catch (ExecutableNotFoundError $e)
        {
        log_message('Required executable not found.', false);
        log_message($e->getMessage()
            . ' not found in the specified paths: '
            . implode(', ', PosixTools::getPaths()), false);
        exit(1);
        }

        return $options;
    }
}
