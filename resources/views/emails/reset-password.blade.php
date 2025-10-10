@component('mail::message')

# ایمیل بازنشانی رمز عبور
### کاربر گرامی سلام
### شما این ایمیل را دریافت کرده اید، زیرا یک درخواست برای بازیابی رمز عبور حساب کاربریتان ارسال کرده اید
### برای بازنشانی رمز عبور کلید زیر را کلیک کنید.
<!-- @component('mail::button', ['url' => `http://localhost:3000/auth/change-password?token={$token}`])
بازیابی رمز عبور
@endcomponent -->
<a href="{{ config('app.frontend_url').'/auth/change-password?token='. $token }}">بازنشانی رمز عبور</a><br><br>
اگر درخواست بازیابی رمز عبور نداده اید ، هیچ اقدام لازم نیست انجام بدهید.<br>

@endcomponent
