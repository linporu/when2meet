<!DOCTYPE html>
<html lang="{{ str_replace("_", "-", app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>建立新活動 - When2Meet</title>
        @vite(["resources/css/app.css", "resources/js/app.js"])
        @vite(["resources/js/pages/events.js"])
    </head>

    <body class="min-h-screen bg-gray-50">
        <div class="py-12">
            <div class="mx-auto max-w-2xl">
                <div class="rounded-lg bg-white p-8 shadow-md">
                    <h1
                        class="mb-8 text-center text-3xl font-bold text-gray-900"
                    >
                        建立新活動
                    </h1>

                    @if ($errors->any())
                        <div
                            class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-600"
                        >
                            <ul class="space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form id="event-form" method="POST" action="/">
                        @csrf

                        <div class="mb-6">
                            <label
                                for="event_name"
                                class="mb-2 block text-sm font-medium text-gray-700"
                            >
                                活動名稱
                                <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                id="event_name"
                                name="event_name"
                                class="w-full rounded-lg border border-gray-300 px-4 py-3 transition-colors outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                value="{{ old("event_name") }}"
                                placeholder="請輸入活動名稱"
                                required
                            />
                        </div>

                        <div class="mb-6">
                            <label
                                for="date"
                                class="mb-2 block text-sm font-medium text-gray-700"
                            >
                                日期
                                <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="date"
                                id="date"
                                name="date"
                                class="w-full rounded-lg border border-gray-300 px-4 py-3 transition-colors outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                value="{{ old("date") }}"
                                required
                            />
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="mb-6">
                                <label
                                    for="start_time"
                                    class="mb-2 block text-sm font-medium text-gray-700"
                                >
                                    開始時間
                                    <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="time"
                                    id="start_time"
                                    name="start_time"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-3 transition-colors outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                    value="{{ old("start_time", "09:00") }}"
                                    required
                                />
                            </div>

                            <div class="mb-6">
                                <label
                                    for="end_time"
                                    class="mb-2 block text-sm font-medium text-gray-700"
                                >
                                    結束時間
                                    <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="time"
                                    id="end_time"
                                    name="end_time"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-3 transition-colors outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                    value="{{ old("end_time", "17:00") }}"
                                    required
                                />
                            </div>
                        </div>

                        <div class="mb-6">
                            <label
                                for="timezone"
                                class="mb-2 block text-sm font-medium text-gray-700"
                            >
                                時區
                                <span class="text-red-500">*</span>
                            </label>
                            <select
                                id="timezone"
                                name="timezone"
                                class="w-full rounded-lg border border-gray-300 px-4 py-3 transition-colors outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                required
                            >
                                <option value="">請選擇時區</option>
                                <option
                                    value="Asia/Taipei"
                                    {{ old("timezone") == "Asia/Taipei" ? "selected" : "" }}
                                >
                                    台北 (UTC+8)
                                </option>
                                <option
                                    value="Asia/Tokyo"
                                    {{ old("timezone") == "Asia/Tokyo" ? "selected" : "" }}
                                >
                                    東京 (UTC+9)
                                </option>
                                <option
                                    value="Asia/Shanghai"
                                    {{ old("timezone") == "Asia/Shanghai" ? "selected" : "" }}
                                >
                                    上海 (UTC+8)
                                </option>
                                <option
                                    value="Asia/Hong_Kong"
                                    {{ old("timezone") == "Asia/Hong_Kong" ? "selected" : "" }}
                                >
                                    香港 (UTC+8)
                                </option>
                                <option
                                    value="Asia/Singapore"
                                    {{ old("timezone") == "Asia/Singapore" ? "selected" : "" }}
                                >
                                    新加坡 (UTC+8)
                                </option>
                                <option
                                    value="UTC"
                                    {{ old("timezone") == "UTC" ? "selected" : "" }}
                                >
                                    UTC (UTC+0)
                                </option>
                                <option
                                    value="America/New_York"
                                    {{ old("timezone") == "America/New_York" ? "selected" : "" }}
                                >
                                    紐約 (UTC-5)
                                </option>
                                <option
                                    value="America/Los_Angeles"
                                    {{ old("timezone") == "America/Los_Angeles" ? "selected" : "" }}
                                >
                                    洛杉磯 (UTC-8)
                                </option>
                                <option
                                    value="Europe/London"
                                    {{ old("timezone") == "Europe/London" ? "selected" : "" }}
                                >
                                    倫敦 (UTC+0)
                                </option>
                            </select>
                        </div>

                        <div class="mb-6">
                            <button
                                type="submit"
                                class="w-full rounded-lg bg-blue-600 px-6 py-3 font-semibold text-white transition-colors outline-none hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                            >
                                建立活動
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </body>
</html>
