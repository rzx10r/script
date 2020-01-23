<?php

/**
 * Generate all models for each schema
 * on postgres.
 * 
 * @author renato
 *
 */
class IndexController extends \Phalcon\Mvc\Controller
{
    
    /**
     * @param int $abstract => 1 generate abstract
     * @param int $clear => 1 clear old before
     * @param string $project
     */
    public function modelsAction(int $abstract = 0, int $clear = 0, string $project = 'project-name'): void
    {       
        $sql = "SELECT nspname as schema FROM pg_catalog.pg_namespace WHERE nspname NOT IN('pg_toast','pg_temp_1','pg_toast_temp_1','pg_catalog','public','information_schema')";
        $fetchSchemas = $this->db->fetchAll($sql);
        $commands = [
            'cd /var/www/html/' . $project
        ];
        
        foreach ($fetchSchemas as $row) {
            $schema = $row['schema'];
            $schemaUc = ucfirst($row['schema']);
            
            $configFile = realpath(PATH_CONFIG . DS . 'config_webtools.php');
            
            $extends = '\\\System\\\Mvc\\\Model';
            if ($abstract == 1) {
                $dir = realpath(PATH_MODELS . DS . 'Abstracts' . DS . $schemaUc);
                $nameSpace = sprintf('App\\\Models\\\Abstracts\\\%s', $schemaUc);
                if (! realpath(PATH_MODELS . DS . 'Abstracts')) {
                    File::criarDir(PATH_MODELS . DS . 'Abstracts');
                }
                
                if (! $dir) {
                    File::criarDir(PATH_MODELS . DS . 'Abstracts' . DS . $schemaUc);
                    $dir = realpath(PATH_MODELS . DS . 'Abstracts' . DS . $schemaUc);
                }
            } else {
                $dir = realpath(PATH_MODELS . DS . $schemaUc);
                $nameSpace = sprintf('App\\\Models\\\%s', $schemaUc);
                if (! $dir) {
                    File::criarDir(PATH_MODELS . DS . $schemaUc);
                    $dir = realpath(PATH_MODELS . DS . $schemaUc);
                }
            }
            
            if ($clear and ($schema != 'system' or $abstract == 1)) {
                $di = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS);
                $ri = new \RecursiveIteratorIterator($di, \RecursiveIteratorIterator::CHILD_FIRST);
                foreach ($ri as $file) {
                    if ($file->getFilename() === '.gitignore' or $file->isDir()) {
                        continue;
                    }
                    unlink($file);
                }
            }
            
            if ($abstract == 1) {
                $options = [
                    '--doc',
                    'relations',
                    'fk',
                    'annotate',
                    'force',
                    'get-set',
                    'abstract'
                ];
            } else {
                $options = [
                    '--force'
                ];
            }
            
            $commands[] = sprintf('phalcon all-models --schema=%s --namespace=%s --extends=%s --output=%s --config=%s %s 2>&1', $schema, $nameSpace, $extends, $dir, $configFile, join(' --', $options));
        }
        exec(join(' && ', $commands), $verbose, $returnValue);
        
        $verbose = array_filter($verbose);
        $return = [
            'return' => $returnValue,
            'command' => $commands,
            'log' => $verbose
        ];
        
        var_dump($return);
        exit();
    }
}
