/**
 * 時區轉換工具
 * 將 UTC 時間轉換為用戶本地時區時間
 */

class TimezoneConverter {
    constructor() {
        this.userTimezone = this.detectUserTimezone();
        this.timezoneOffset = this.getTimezoneOffset();
    }

    /**
     * 自動偵測用戶時區
     */
    detectUserTimezone() {
        try {
            return Intl.DateTimeFormat().resolvedOptions().timeZone;
        } catch (error) {
            console.warn("無法偵測時區，使用 UTC:", error);
            return "UTC";
        }
    }

    /**
     * 取得時區偏移顯示 (如 GMT+8)
     */
    getTimezoneOffset() {
        const now = new Date();
        const offsetMinutes = now.getTimezoneOffset();
        const offsetHours = Math.abs(offsetMinutes / 60);
        const sign = offsetMinutes <= 0 ? "+" : "-";
        return `GMT${sign}${offsetHours}`;
    }

    /**
     * 將 UTC 時間字符串轉換為本地時間
     * @param {string} utcTimeString - UTC 時間 (格式: "01:00:00" 或 "01:00")
     * @returns {string} 本地時間 (格式: "09:00")
     */
    convertUtcToLocal(utcTimeString) {
        try {
            // 處理時間格式，確保為 HH:MM:SS
            const normalizedTime = this.normalizeTimeString(utcTimeString);

            // 創建 UTC 日期物件 (使用任意日期，只關心時間)
            const utcDate = new Date(`1970-01-01T${normalizedTime}Z`);

            // 轉換為本地時間並格式化
            return utcDate.toLocaleTimeString("en-GB", {
                hour12: false,
                hour: "2-digit",
                minute: "2-digit",
            });
        } catch (error) {
            console.error("時間轉換失敗:", error, utcTimeString);
            return utcTimeString; // 返回原始時間作為備案
        }
    }

    /**
     * 標準化時間字符串格式
     * @param {string} timeString - 時間字符串
     * @returns {string} 標準化的時間字符串 (HH:MM:SS)
     */
    normalizeTimeString(timeString) {
        if (!timeString) return "00:00:00";

        // 移除空白字符
        timeString = timeString.trim();

        // 如果是 HH:MM 格式，添加秒數
        if (timeString.match(/^\d{1,2}:\d{2}$/)) {
            timeString += ":00";
        }

        // 確保小時為兩位數
        if (timeString.match(/^\d:\d{2}:\d{2}$/)) {
            timeString = "0" + timeString;
        }

        return timeString;
    }

    /**
     * 初始化頁面時區轉換
     */
    initializePageTimezone() {
        // 查找所有需要時區轉換的元素
        const timezoneElements = document.querySelectorAll(".timezone-display");

        timezoneElements.forEach((element) => {
            const utcStart = element.getAttribute("data-utc-start");
            const utcEnd = element.getAttribute("data-utc-end");

            if (utcStart && utcEnd) {
                const localStart = this.convertUtcToLocal(utcStart);
                const localEnd = this.convertUtcToLocal(utcEnd);

                // 更新顯示內容
                element.innerHTML = `${localStart} - ${localEnd} <small class="text-gray-500">(${this.timezoneOffset})</small>`;
            }
        });
    }
}

// 頁面載入完成後初始化時區轉換
document.addEventListener("DOMContentLoaded", function () {
    const converter = new TimezoneConverter();
    converter.initializePageTimezone();
});

// 匯出供其他模組使用
window.TimezoneConverter = TimezoneConverter;
