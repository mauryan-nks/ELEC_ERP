<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Shield\Entities\User;

class CreateShopOwner extends BaseCommand
{
    protected $group='Shop';
    protected $name='shop:create-owner';
    protected $description='Create the first owner login for this single-shop installation.';
    protected $usage='shop:create-owner [email] [password] [username] [fullName] [phone]';

    public function run(array $params)
    {
        $email=$params[0]??CLI::prompt('Owner email');
        $password=$params[1]??CLI::prompt('Password');
        $username=$params[2]??CLI::prompt('Username','owner');
        $fullName=$params[3]??CLI::prompt('Full name',$username ?: 'Owner');
        $phone=$params[4]??CLI::prompt('Phone','');
        if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($password)<8){ CLI::error('Valid email and password of at least 8 characters required.'); return; }

        $users=auth()->getProvider(); $user=$users->findByCredentials(['email'=>$email]);
        if(!$user){
            $entity=new User(['username'=>$username ?: null,'email'=>$email,'password'=>$password]);
            $users->save($entity); $user=$users->findById($users->getInsertID());
        } else {
            $user->fill(['username'=>$username ?: $user->username,'password'=>$password]); $users->save($user); $user=$users->findById($user->id);
        }
        $user->syncGroups('owner'); if($user->isBanned()) $user->unBan();
        $now=date('Y-m-d H:i:s');
        db_connect()->query(
            'INSERT INTO staff_profiles (user_id,full_name,phone,created_at,updated_at) VALUES (?,?,?,?,?) '
            . 'ON DUPLICATE KEY UPDATE full_name=VALUES(full_name),phone=VALUES(phone),updated_at=VALUES(updated_at)',
            [$user->id,$fullName ?: $username,$phone ?: null,$now,$now]
        );
        CLI::write('Owner ready: '.$email,'green');
    }
}
