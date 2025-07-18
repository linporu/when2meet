# Product Requirements Document: When2Meet Clone

## 1. Overview

This document outlines the product requirements for a clone of the When2Meet web application. The primary goal of this project is to serve as a learning exercise for backend development with Laravel, with a focus on creating a functional, user-friendly scheduling tool. The final product will be deployed to an AWS environment with SELinux.

## 2. Goals and Objectives

-   **Core Functionality:** Replicate the essential features of When2Meet, allowing users to create events, share them, and indicate their availability.
-   **Tech Stack:** Utilize Laravel and Blade for the backend and frontend, respectively.
-   **Deployment:** Successfully deploy the application to an AWS server running SELinux.
-   **Learning:** Provide a practical learning experience for building and deploying a real-world web application.

## 3. Target Audience

This project is primarily for the developer's own educational purposes. However, the final product should be intuitive enough for anyone to use for scheduling purposes.

## 4. Core Features (To Be Discussed and Prioritized)

| Feature ID | Feature Name                                      | Description                                                                                                                                                                                                                                                                    | Priority (High/Medium/Low) |
| :--------- | :------------------------------------------------ | :----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | :------------------------- |
| F01        | **建立活動 (Create Event)**                       | - 使用者可以在首頁 (`/`) 建立新活動。<br>- 可以輸入活動名稱（必填）。<br>- 提供兩種日期選擇模式：「特定日期」(Specific Dates) 或「星期幾」(Days of Week)。<br>- 可以設定活動的開始時間、結束時間和時區。<br>- 成功建立後，產生一個獨一無二的分享連結 (e.g., `/event/{hash}`)。 | **High**                   |
| F02        | **參與活動 (Participate in Event)**               | - 透過分享連結進入活動頁面。<br>- 參與者需要輸入一個名稱來登入該活動。<br>- 提供一個選填的密碼，用於後續修改自己的時間。                                                                                                                                                       | **High**                   |
| F03        | **標示有空時間 (Mark Availability)**              | - 在活動頁面上，顯示一個基於活動日期與時間的時間格 (Time Grid)。<br>- 使用者可以透過在時間格上拖拉來選取多個有空的時段。<br>- 再次拖拉已選取的時段可以取消選取。                                                                                                               | **High**                   |
| F04        | **視覺化團隊時間 (Visualize Group Availability)** | - 時間格會即時顯示所有參與者的有空時間重疊狀況。<br>- 使用顏色梯度（例如：從淺到深）來表示每個時段的空閒人數多寡，人越多顏色越深/越綠。<br>- 當滑鼠懸停在某個時段上時，顯示該時段有哪些人有空、哪些人沒空。                                                                    | **High**                   |
| F05        | **關於頁面 (About Page)**                         | - 建立一個簡單的 `/about` 頁面，說明網站用途。                                                                                                                                                                                                                                 | **Medium**                 |

## 5. Technical Requirements

-   **Backend:** Laravel
-   **Frontend:** Blade templates with basic CSS and JavaScript
-   **Database:** SQLite for local development, potentially transitioning to a more robust database on AWS (e.g., MySQL, PostgreSQL).
-   **Server:** AWS EC2 instance
-   **Operating System:** Amazon Linux (or similar) with SELinux enabled.

## 6. Out of Scope

-   User accounts and authentication (at least for the initial version).
-   Advanced features not present in the original When2Meet (e.g., calendar integrations, reminders).
-   Complex UI/UX enhancements beyond the basic functionality.

---

**開發注意事項 (Development Notes):**

-   **彈性範圍 (Flexible Scope):** 根據 mentor 的建議，部分功能可以根據開發進度進行簡化。特別是前端互動較複雜的部分（如：F04 的顏色梯度、F03 的拖拉選擇時間），在初期開發階段可以採用較簡單的替代方案（例如：點擊選擇、手動輸入時間），以確保核心後端功能優先完成。

---
