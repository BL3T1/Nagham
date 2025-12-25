<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>نظام إدارة عيادة نغم</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <style>
            :root {
                --primary-color: #e1caaa;
                --secondary-color: #26533e;
            }
        </style>
    </head>
    <body class="min-h-screen flex items-center justify-center" style="background-color: #13291e;">
        <div class="max-w-4xl w-full px-6 py-12">
            <div class="text-center mb-12">
                <img src="{{ asset('logo.png') }}" alt="Logo" class="mx-auto mb-6" style="height: 200px; width: auto;">
                <h1 class="text-4xl font-bold text-white mb-4">عيادة نغم</h1>
                <p class="text-lg" style="color: var(--primary-color);">اختر البوابة الخاصة بك للمتابعة</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Admin Panel -->
                <a href="/admin" class="group block p-6 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 border-t-4" style="background-color: white; border-color: var(--primary-color);">
                    <div class="flex flex-col items-center">
                        <div class="p-4 rounded-full mb-4 transition-colors" style="background-color: var(--primary-color);">
                            <svg class="w-8 h-8" style="color: var(--secondary-color);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                        <h2 class="text-xl font-semibold mb-2" style="color: var(--secondary-color);">لوحة الإدارة</h2>
                        <p class="text-sm text-center" style="color: #666;">إعدادات النظام والإدارة</p>
                    </div>
                </a>

                <!-- Reception Panel -->
                <a href="/reception" class="group block p-6 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 border-t-4" style="background-color: white; border-color: var(--primary-color);">
                    <div class="flex flex-col items-center">
                        <div class="p-4 rounded-full mb-4 transition-colors" style="background-color: var(--primary-color);">
                            <svg class="w-8 h-8" style="color: var(--secondary-color);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <h2 class="text-xl font-semibold mb-2" style="color: var(--secondary-color);">الاستقبال</h2>
                        <p class="text-sm text-center" style="color: #666;">تسجيل المرضى والمواعيد</p>
                    </div>
                </a>

                <!-- Doctor Panel -->
                <a href="/doctor" class="group block p-6 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 border-t-4" style="background-color: white; border-color: var(--primary-color);">
                    <div class="flex flex-col items-center">
                        <div class="p-4 rounded-full mb-4 transition-colors" style="background-color: var(--primary-color);">
                            <svg class="w-8 h-8" style="color: var(--secondary-color);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                            </svg>
                        </div>
                        <h2 class="text-xl font-semibold mb-2" style="color: var(--secondary-color);">لوحة الطبيب</h2>
                        <p class="text-sm text-center" style="color: #666;">السجلات الطبية والاستشارات</p>
                    </div>
                </a>
            </div>

            <div class="mt-12 text-center text-sm" style="color: var(--primary-color);">
                <p>&copy; {{ date('Y') }} عيادة نغم. جميع الحقوق محفوظة.</p>
            </div>
        </div>
    </body>
</html>