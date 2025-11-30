<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * @test
     * メールアドレスが未入力の場合、バリデーションメッセージが表示される（一般ユーザー）
     */
    public function login_userEmail_validationMessage()
    {
        $response = $this->post('/login', [
            'email' => '',
            'password' => 'password',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    /**
     * @test
     * パスワードが未入力の場合、バリデーションメッセージが表示される（一般ユーザー）
     */
    public function login_userPassword_validationMessage()
    {
        $response = $this->post('/login', [
            'email' => 'general1@example.com',
            'password' => '',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    /**
     * @test
     * 登録内容と一致しない場合、バリデーションメッセージが表示される（一般ユーザー）
     */
    public function login_user_validationMessage()
    {
        $response = $this->post('/login', [
            'email' => 'general1@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません']);
    }

    /**
     * @test
     */
    public function login_adminEmail_validationMessage()
    {
        $response = $this->post('/login', [
            'email' => '',
            'password' => Hash::make(env('ADMIN_PASSWORD', 'adminpassword')),
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    /**
     * @test
     * パスワードが未入力の場合、バリデーションメッセージが表示される（管理者）
     */
    public function login_adminPassword_validationMessage()
    {
        $response = $this->post('/login', [
            'email' => env('ADMIN_EMAIL', 'admin@example.com'),
            'password' => '',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    /**
     * @test
     * 登録内容と一致しない場合、バリデーションメッセージが表示される（管理者）
     */
    public function login_admin_validationMessage()
    {
        $response = $this->post('/login', [
            'email' => env('ADMIN_EMAIL', 'admin@example.com'),
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません']);
    }
}
