<?php

namespace App\Http\Controllers\User;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Zend\Diactoros\Response\JsonResponse;
use Laravel\Passport\Client as PClient;
use App\Http\Resources\User as UserResource;

class UserController extends Controller
{
    /** @var PClient $pclient */
    private $pclient;

    /**
     * Constructor
     * @return void
     */
    public function __construct()
    {
        $this->pclient = PClient::find(2);
    }

    /**
     * Register a new user
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse
     */
    public function register(Request $request)
    {
        Log::info('Request to register a new user');
        Log::debug($request->all());
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'name' => 'required',
            'password' => 'required'
        ]);

        if($validator->fails()) {
            return response()->json(['status' => 'Declined', 'code' => 422, 'errors' => $validator->errors()],422);
        }

        $user = new User();
        $user->email = $request->email;
        $user->name = $request->name;
        $user->password = app('hash')->make($request->password);
        $user->save();

        $data = [
            'grant_type' => 'password',
            'client_id' => $this->pclient->id,
            'client_secret' => $this->pclient->secret,
            'username' => $user->email,
            'password' => $request->password,
            'scope' => '*',
        ];

        $headers = [
            'Content-Type' => 'application/json'
        ];

        $url = url('/oauth/token');
        Log::info('The OAuth endpoint '.$url);

        $result = sendPost($url,$data,$headers,'json','POST');
        Log::info('Response from generating token');

        //Add the User to the response

        return response()->json(['auth' => $result, 'user' => new UserResource($user)]);
    }

    /**
     * Log a user in given the email and password
     * @param \Illuminate\Http\Request $request
     * @return mixed
     */
    public function login(Request $request)
    {
        Log::info('Request to Login');
        Log::debug($request->all());
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if($validator->fails()) {
            return response()->json(['status' => 'Declined', 'code' => 422, 'errors' => $validator->errors()],422);
        }

        $user = User::where('email',$request->email)->first();

        if(!$user instanceof User) {
            return errorResponse([],'Error',404,'User not found');
        } else {
            //check for the password correctness
            if(Hash::check($request->password,$user->password)) {
                $data = [
                    'grant_type' => 'password',
                    'client_id' => $this->pclient->id,
                    'client_secret' => $this->pclient->secret,
                    'username' => $user->email,
                    'password' => $request->password,
                    'scope' => '*',
                ];

                $headers = [
                    'Content-Type' => 'application/json'
                ];

                $url = url('/oauth/token');
                Log::info('The OAuth endpoint '.$url);

                $result = sendPost($url,$data,$headers,'json','POST');
                Log::info('Response from LOGIN Token');

                return response()->json(['auth' => $result, 'user' => new UserResource($user)]);
            }else {
                return errorResponse([],'Declined',400,'Wrong username or password');
            }
        }
    }

    /**
     * Log the attempted access to see what could be wrong
     * @param \Illuminate\Http\Request
     * @return mixed
     */
    public function test(Request $request)
    {
        Log::info('Request to Test Details');
        Log::debug($request->all());
        Log::debug($request->header());

        return "Done";
    }
}
