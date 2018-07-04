use App\Models\User;

class AddDefaultUser extends Migration
{
    use MigrationTrait;

    public function up()
    {
        if (config('app.env') != 'testing') {
            User::create([
                'name'     => '{{$name}}',
                'email'    => '{{$email}}',
                'password' => bcrypt('{{$password}}')
            ]);
        }
    }

    public function down()
    {
        if (config('app.env') != 'testing') {
            User::where('email', '{{$email}}')->delete();
        }
    }
}