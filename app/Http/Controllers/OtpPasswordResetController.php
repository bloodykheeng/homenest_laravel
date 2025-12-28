<?php

namespace App\Http\Controllers;

use App\Models\OtpRequest;
use App\Models\User;
use App\Traits\SendSmsNotificationTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class OtpPasswordResetController extends Controller
{
    use SendSmsNotificationTrait;

    /**
     * Send OTP for password reset
     * First function: Receives email/phone and sends OTP
     */
    public function getOtpForPasswordReset(Request $request)
    {
        try {
            // Validate input
            $request->validate([
                'email' => 'required|string',
            ]);

            $input = $request->email;

            // Check if it's a valid email or phone number
            $isEmail = filter_var($input, FILTER_VALIDATE_EMAIL);
            $isPhone = preg_match('/^\d{12}$/', $input); // Matches 256XXXXXXXXX (without +)

            if (!$isEmail && !$isPhone) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please provide a valid email or phone number in format test@example.com or +256XXXXXXXXX',
                ], 422);
            }

            // Find user by email or phone
            $user = User::where('email', $input)
                ->orWhere('phone', $input)
                ->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.',
                ], 404);
            }

            // Generate OTP
            $otp = rand(100000, 999999);

            // Delete any existing OTP for this user
            OtpRequest::where('user_id', $user->id)->delete();

            // Create new OTP record
            $codeRecord = OtpRequest::create([
                'email' => $user->email,
                'code' => $otp,
                'user_id' => $user->id,
                'created_at' => now(),
            ]);

            // Send OTP via email if user has email
            if ($user->email) {
                Mail::send('emails.password-reset.password_reset_otp', ['otp' => $otp, 'user' => $user], function ($message) use ($user) {
                    $message->to($user->email)->subject('Password Reset OTP Code');
                });
            }

            // Send OTP via SMS if user has phone
            if ($user->phone) {
                $message = "Your password reset OTP code is: {$otp}. This code will expire in 10 minutes. \nCitizen Feedback Platform";
                $this->sendSmsNotification('single', $user->phone, $message);
            }

            return response()->json([
                'success' => true,
                'message' => 'OTP has been sent to your email and/or phone number.',
                'data' => [
                    'email_sent' => $user->email ? true : false,
                    'sms_sent' => $user->phone ? true : false,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send OTP: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate OTP
     * Second function: Validates the OTP for the user
     */
    public function validateOtp(Request $request)
    {
        try {
            // Validate input
            $request->validate([
                'email' => 'required|string',
                'otp' => 'required|string|size:6',
            ]);

            $input = $request->email;
            $otp = $request->otp;

            // Find user by email or phone
            $user = User::where('email', $input)
                ->orWhere('phone', $input)
                ->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.',
                ], 404);
            }

            // Find the OTP record
            $codeRecord = OtpRequest::where('user_id', $user->id)
                ->where('code', $otp)
                ->first();

            if (!$codeRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid OTP code.',
                ], 422);
            }

            // Check if OTP is expired (10 minutes)
            $createdAt = Carbon::parse($codeRecord->created_at);
            $now = Carbon::now();
            $diffInMinutes = $now->diffInMinutes($createdAt);

            if ($diffInMinutes > 10) {
                // Delete expired OTP
                $codeRecord->delete();

                return response()->json([
                    'success' => false,
                    'message' => 'OTP code has expired. Please request a new one.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'OTP validated successfully.',
                'data' => [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to validate OTP: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reset Password with OTP
     * Third function: Receives OTP, new password, and confirm password
     */
    public function resetPasswordWithOtp(Request $request)
    {
        try {
            // Validate input
            $request->validate([
                'email' => 'required|string',
                'otp' => 'required|string|size:6',
                'password' => 'required|string|min:8|confirmed',
                'password_confirmation' => 'required|string|min:8',
            ]);

            $input = $request->email;
            $otp = $request->otp;
            $newPassword = $request->password;

            // Find user by email or phone
            $user = User::where('email', $input)
                ->orWhere('phone', $input)
                ->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.',
                ], 404);
            }

            // Find and validate the OTP record
            $codeRecord = OtpRequest::where('user_id', $user->id)
                ->where('code', $otp)
                ->first();

            if (!$codeRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid OTP code.',
                ], 422);
            }

            // Check if OTP is expired (10 minutes)
            $createdAt = Carbon::parse($codeRecord->created_at);
            $now = Carbon::now();
            $diffInMinutes = $now->diffInMinutes($createdAt);

            if ($diffInMinutes > 10) {
                // Delete expired OTP
                $codeRecord->delete();

                return response()->json([
                    'success' => false,
                    'message' => 'OTP code has expired. Please request a new one.',
                ], 422);
            }

            // Update user password
            $user->password = Hash::make($newPassword);
            $user->save();

            // Delete the OTP record after successful password reset
            $codeRecord->delete();

            // Send confirmation email
            if ($user->email) {
                Mail::send('emails.password-reset.password_reset_confirmation', ['user' => $user], function ($message) use ($user) {
                    $message->to($user->email)->subject('Password Reset Successful');
                });
            }

            // Send confirmation SMS
            if ($user->phone) {
                $message = "Your password has been successfully reset. If you did not make this change, please contact support immediately. \nCitizen Feedback Platform";
                $this->sendSmsNotification('single', $user->phone, $message);
            }

            return response()->json([
                'success' => true,
                'message' => 'Password has been reset successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset password: ' . $e->getMessage(),
            ], 500);
        }
    }
}
