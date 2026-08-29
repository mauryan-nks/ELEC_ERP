<?php

namespace App\Controllers;

use CodeIgniter\Shield\Entities\User;

class Users extends BaseController
{
    public function index(): string
    {
        $db=db_connect(); $provider=auth()->getProvider();
        $users=$provider->withIdentities()->withGroups()->orderBy('id','ASC')->findAll();
        $profiles=$db->table('staff_profiles')->get()->getResultArray();
        $profileMap=[]; foreach($profiles as $p) $profileMap[(int)$p['user_id']]=$p;
        $rows=[];
        foreach($users as $u){
            $profile=$profileMap[(int)$u->id] ?? [];
            $rows[]=[
                'id'=>(int)$u->id,'full_name'=>$profile['full_name'] ?? null,'phone'=>$profile['phone'] ?? null,
                'username'=>$u->username,'email'=>$u->email,'groups'=>$u->getGroups(),'active'=>$u->isBanned()?0:1,
            ];
        }
        return view('users/index',['title'=>'Users & Roles','rows'=>$rows,'groups'=>array_keys(config('AuthGroups')->groups)]);
    }

    public function store()
    {
        $email=trim((string)$this->request->getPost('email')); $password=(string)$this->request->getPost('password');
        $username=trim((string)$this->request->getPost('username')); $fullName=trim((string)$this->request->getPost('full_name'));
        $phone=trim((string)$this->request->getPost('phone')); $group=(string)$this->request->getPost('group');
        $allowed=array_keys(config('AuthGroups')->groups);
        if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($password)<8||!in_array($group,$allowed,true)){
            return redirect()->back()->withInput()->with('error','Valid email, password of at least 8 characters, and role are required.');
        }
        try{
            $provider=auth()->getProvider();
            if($provider->findByCredentials(['email'=>$email])) return redirect()->back()->withInput()->with('error','That email is already registered.');
            $user=new User(['username'=>$username ?: null,'email'=>$email,'password'=>$password]);
            $provider->save($user); $user=$provider->findById($provider->getInsertID());
            $user->syncGroups($group);
            db_connect()->table('staff_profiles')->insert([
                'user_id'=>$user->id,'full_name'=>$fullName ?: ($username ?: null),'phone'=>$phone ?: null,
                'created_by'=>auth()->id(),'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')
            ]);
            return redirect()->to('/users')->with('message','Staff login created.');
        }catch(\Throwable $e){ return redirect()->back()->withInput()->with('error',$e->getMessage()); }
    }

    public function update(int $id)
    {
        $email=trim((string)$this->request->getPost('email')); $password=(string)$this->request->getPost('password');
        $username=trim((string)$this->request->getPost('username')); $fullName=trim((string)$this->request->getPost('full_name'));
        $phone=trim((string)$this->request->getPost('phone')); $group=(string)$this->request->getPost('group');
        $allowed=array_keys(config('AuthGroups')->groups);
        if(!filter_var($email,FILTER_VALIDATE_EMAIL)||($password!==''&&strlen($password)<8)||!in_array($group,$allowed,true)){
            return redirect()->back()->withInput()->with('error','Enter a valid email/role. New password must be at least 8 characters.');
        }
        try{
            $provider=auth()->getProvider(); $user=$provider->findById($id);
            if(!$user) throw new \RuntimeException('User not found.');
            $other=$provider->findByCredentials(['email'=>$email]);
            if($other && (int)$other->id!==$id) throw new \RuntimeException('That email is already registered.');
            $data=['username'=>$username ?: null,'email'=>$email]; if($password!=='') $data['password']=$password;
            $user->fill($data); $provider->save($user); $user=$provider->findById($id); $user->syncGroups($group);
            $db=db_connect();
            $db->query(
                'INSERT INTO staff_profiles (user_id,full_name,phone,created_by,created_at,updated_at) VALUES (?,?,?,?,?,?) '
                . 'ON DUPLICATE KEY UPDATE full_name=VALUES(full_name),phone=VALUES(phone),updated_at=VALUES(updated_at)',
                [$id,$fullName ?: ($username ?: null),$phone ?: null,auth()->id(),date('Y-m-d H:i:s'),date('Y-m-d H:i:s')]
            );
            return redirect()->to('/users')->with('message','User updated.');
        }catch(\Throwable $e){ return redirect()->back()->withInput()->with('error',$e->getMessage()); }
    }

    public function status(int $id)
    {
        try{
            if($id===(int)auth()->id()) throw new \RuntimeException('You cannot disable your own current login.');
            $provider=auth()->getProvider(); $user=$provider->findById($id); if(!$user) throw new \RuntimeException('User not found.');
            $enable=(int)$this->request->getPost('active')===1;
            $enable ? $user->unBan() : $user->ban('Disabled by administrator');
            return redirect()->to('/users')->with('message',$enable?'User activated.':'User disabled.');
        }catch(\Throwable $e){ return redirect()->to('/users')->with('error',$e->getMessage()); }
    }
}
