<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Database\Seeders\DatabaseSeeder;
use Dflydev\DotAccessData\Data;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    /**
     * @test
     * 名前が未入力の場合、バリデーションメッセージが表示される
     *
     *  1. 名前以外のユーザー情報を入力する
     *  2. 会員登録の処理を行う
     */
    public function register_user_validate_name()
    {
        $response = $this->post('/register', [
            'name' => '',
            'email' => 'test@gmail.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['name' => 'お名前を入力してください']);
    }

    /**
     * @test
     *  メールアドレスが未入力の場合、バリデーションメッセージが表示される
     *
     * 1. メールアドレス以外のユーザー情報を入力する
     * 2. 会員登録の処理を行う
     */
    public function register_user_validate_email()
    {
        $response = $this->post('/register', [
            'name' => 'testUser',
            'email' => '',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    /**
     * @test
     * パスワードが8文字未満の場合、バリデーションメッセージが表示される
     *
     * 1. パスワードを8文字未満にし、ユーザー情報を入力する
     * 2. 会員登録の処理を行う
     */
    public function register_user_validate_password_under8()
    {
        $response = $this->post('/register', [
            'name' => 'testUser',
            'email' => 'test@gmail.com',
            'password' => 'passwor',
            'password_confirmation' => 'passwor',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['password' => 'パスワードは8文字以上で入力してください']);
    }

    /**
     * @test
     * パスワードが一致しない場合、バリデーションメッセージが表示される
     *
     * 1. 確認用のパスワードとパスワードを一致させず、ユーザー情報を入力する
     * 2. 会員登録の処理を行う"
     */
    public function register_user_validate_confirm_password()
    {
        $response = $this->post('/register', [
            'name' => 'testUser',
            'email' => 'test@gmail.com',
            'password' => 'password',
            'password_confirmation' => 'passwor1',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['password' => 'パスワードと一致しません']);
    }

    /**
     * @test
     * パスワードが未入力の場合、バリデーションメッセージが表示される
     *
     * 1. パスワード以外のユーザー情報を入力する
     * 2. 会員登録の処理を行う"
     */
    public function register_user_validate_password()
    {
        $response = $this->post('/register', [
            'name' => 'testUser',
            'email' => 'test@gmail.com',
            'password' => '',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    /**
     * @test
     * 会員登録後、認証メールが送信される
     *
     * 1. 会員登録をする
     * 2. 認証メールを送信する
     */
    public function a_verification_email_is_sent_upon_registration()
    {
        Notification::fake();

        Notification::assertNothingSent();

        $response = $this->post('/register', [
            'name' => 'testUser',
            'email' => 'test@gmail.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);
        $user = User::where('email', 'test@gmail.com')->first();

        Notification::assertSentTo(
            $user, VerifyEmail::class
        );

        $response->assertRedirect(RouteServiceProvider::HOME);
    }

    /**
     * @test
     * メール認証誘導画面で「認証はこちらから」ボタンを押下するとメール認証サイトに遷移する
     *
     * 1. メール認証導線画面を表示する (URL直接アクセスで代替)
     * 2. 「認証はこちらから」ボタンを押下（認証URLを直接生成してアクセス）
     * 3. メール認証サイトを表示する（認証後のリダイレクト先を確認）
     */

    public function user_can_verify_their_email_by_clicking_the_verification_link()
    {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verfificatinUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        $response = $this->actingAs($user)->get($verfificatinUrl);

        Event::assertDispatched(Verified::class);

        $this->assertTrue($user->fresh()->hasVerifiedEmail());

        $response->assertRedirect(RouteServiceProvider::HOME.'?verified=1');
    }

    /**
     * @test
     * メール認証サイトのメール認証を完了すると、勤怠登録画面に遷移する
     *
     * 1. メール認証を完了する
     * 2. 勤怠登録画面を表示する"
     */
    public function verified_user_is_redirected_to_the_attendance_management_screen()
    {
        $user = User::factory()->unverified()->create();

        $verfificatinUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        $response = $this->actingAs($user)->get($verfificatinUrl);

        $response = $this->actingAs($user)->get(RouteServiceProvider::HOME);

        $response->assertStatus(200);
        $response->assertViewIs('index');
    }
}
