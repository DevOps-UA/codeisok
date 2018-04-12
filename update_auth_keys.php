#!/usr/bin/env php
<?php
require_once(dirname(__FILE__) . '/bootstrap.php');

class UpdateAuthKeys
{
    public function run()
    {
        $Gitosis = new Model_Gitosis();

        $users = $Gitosis->getUsers();
        if ($users === false) {
            echo "Cannot receive users from DB";
            return;
        }
        $this->generateAuthKeys($users);

        $repositories = $Gitosis->getRepositories();
        if ($repositories === false) {
            echo "Cannot receive repositories from DB";
            return;
        }
        $this->createNewRepositories($repositories);
    }

    /**
     * @param array $users
     */
    public function generateAuthKeys($users)
    {
        $auth_keys = '# autogenerated file. Do not edit';
        foreach ($users as $user) {
            foreach (array_filter(explode("\n", $user['public_key'])) as $key) {
                $auth_keys .= PHP_EOL . \GitPHP_Gitosis::formatKeyString(dirname(__FILE__), $user['username'], $key);
            }
        }
        $auth_keys_path = \GitPHP_Gitosis::HOME . \GitPHP_Gitosis::KEYFILE;
        $auth_keys_tmp_path = $auth_keys_path . '.tmp';
        if (false === file_put_contents($auth_keys_tmp_path, $auth_keys)) {
            echo "Cannot write authorized_keys file\n";
            return;
        }
        if (false === rename($auth_keys_tmp_path, $auth_keys_path)) {
            echo "Cannot rename tmp auth keys";
        }
    }

    /**
     * @param array $repositories
     */
    public function createNewRepositories($repositories)
    {
        $root_directory = \GitPHP_Config::GetInstance()->GetValue(\GitPHP_Config::PROJECT_ROOT);
        foreach ($repositories as $repository) {
            $full_path = $root_directory . '/' . $repository['project'];
            if (is_dir($full_path)) {
                continue;
            }

            exec("cd " . $root_directory . "; git init --bare " . escapeshellarg($repository['project']), $out, $retval);
            if ($retval) {
                echo "Cannot create project {$repository['project']}: " . implode("\n", $out) . PHP_EOL;
            }
        }
    }
}

$Application = new GitPHP_Application();
$Application->init();

$Script = new UpdateAuthKeys();
$Script->run();
