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

## 4. Development Phases

### Phase 1: Core Functionality (No Authentication)

| Feature ID | Feature Name                                      | Description                                                                                                                                                                                                 | Priority (High/Medium/Low) |
| :--------- | :------------------------------------------------ | :---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | :------------------------- |
| F01        | **Create Event**                                  | - Users can create new events on the homepage (`/`) without login.<br>- Can input event name (required).<br>- Can set event date, start time, end time, and timezone.<br>- After successful creation, generates a unique shareable link (`/{event-hash}`).                    | **High**                   |
| F02        | **Participate in Event**                          | - Access event page through shareable link (`/{event-hash}`).<br>- Participants need to enter a name to join the event.                                                                                                                                                            | **High**                   |
| F03        | **Mark Availability**                             | - Display a time grid based on event date and time on the event page.<br>- Users can drag on the time grid to select multiple available time slots.<br>- Dragging on already selected slots will deselect them.                                                               | **High**                   |
| F04        | **Visualize Group Availability**                  | - Time grid displays real-time overlap of all participants' availability.<br>- Use color gradients (e.g., light to dark) to represent the number of available people per slot - more people means darker/greener color.<br>- Show who's available/unavailable on hover. | **High**                   |

### Phase 2: User Authentication System (Future Enhancement)

| Feature ID | Feature Name                             | Description                                                                                                                                                                                               | Priority (High/Medium/Low) |
| :--------- | :--------------------------------------- | :-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | :------------------------- |
| F06        | **User Authentication**                           | - Change homepage (`/`) to login page, providing user login and registration functionality.<br>- After login, redirect to `/dashboard` page to create events.<br>- Provide logout functionality.<br>- Use Laravel's built-in authentication mechanism.<br>- Event pages (`/{event-hash}`) remain publicly accessible. | **Future**                 |
| F07        | **User Event Management**                         | - Logged-in users can view all events they created.<br>- Provide edit and delete event functionality.                                                                                                                                                                              | **Future**                 |

## 5. Technical Requirements

### Phase 1 Requirements

-   **Backend:** Laravel
-   **Frontend:** Blade templates with basic CSS and JavaScript
-   **Database:** SQLite for local development, potentially transitioning to a more robust database on AWS (e.g., MySQL, PostgreSQL).
-   **Server:** AWS EC2 instance
-   **Operating System:** Amazon Linux (or similar) with SELinux enabled.

### Phase 2 Requirements (Future)

-   **Authentication:** Laravel Breeze or built-in Auth scaffolding
-   **User Management:** Laravel's user model and migration system

## 6. Out of Scope

-   Advanced features not present in the original When2Meet (e.g., calendar integrations, reminders).
-   Complex UI/UX enhancements beyond the basic functionality.
-   Email notifications and reminders.

---

**Development Notes:**

-   **Flexible Scope:** Based on mentor's suggestions, some features can be simplified according to development progress. Particularly for complex frontend interactions (such as color gradients in F04, drag selection in F03), simpler alternatives can be adopted in early development stages (e.g., click selection, manual time input) to ensure core backend functionality is prioritized.

---
