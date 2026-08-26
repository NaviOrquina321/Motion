from playwright.sync_api import sync_playwright

def run_cuj(page):
    # 1. Landing Page
    page.goto("http://localhost:8000/index.php")
    page.wait_for_timeout(1000)
    page.screenshot(path="/home/jules/verification/screenshots/01_landing_page.png")

    # 2. Login as Student
    page.goto("http://localhost:8000/login.php")
    page.wait_for_timeout(500)
    page.fill("input[name='email']", "john.doe@student.com")
    page.fill("input[name='password']", "password123")
    page.click("button[type='submit']")
    page.wait_for_timeout(1000)
    page.screenshot(path="/home/jules/verification/screenshots/02_student_dashboard.png")

    # 3. AI Tutor Matching
    page.goto("http://localhost:8000/student/ai-matching.php")
    page.wait_for_timeout(1000)
    page.select_option("select[name='level']", "Intermediate")
    page.click("button[type='submit']")
    page.wait_for_timeout(1000)
    page.screenshot(path="/home/jules/verification/screenshots/03_ai_matching.png")

    # 4. Tutor Discovery & Booking
    page.goto("http://localhost:8000/student/find-tutor.php")
    page.wait_for_timeout(1000)
    page.screenshot(path="/home/jules/verification/screenshots/04_find_tutor.png")

    # 5. Tutor Profile
    page.goto("http://localhost:8000/student/tutor-profile.php?id=1")
    page.wait_for_timeout(1000)
    page.screenshot(path="/home/jules/verification/screenshots/05_tutor_profile.png")

    # 6. Booking Session
    page.goto("http://localhost:8000/student/booking.php?tutor_id=1&subject_id=1")
    page.wait_for_timeout(1000)
    page.screenshot(path="/home/jules/verification/screenshots/06_booking.png")

    # 7. GCash Payment
    page.goto("http://localhost:8000/student/payments.php?booking_id=1")
    page.wait_for_timeout(1000)
    page.screenshot(path="/home/jules/verification/screenshots/07_gcash_payment.png")

    # 8. Student Calendar
    page.goto("http://localhost:8000/student/calendar.php")
    page.wait_for_timeout(1000)
    page.screenshot(path="/home/jules/verification/screenshots/08_student_calendar.png")

    # 9. Login as Tutor
    page.goto("http://localhost:8000/login.php")
    page.fill("input[name='email']", "maria.santos@tutorlink.com")
    page.fill("input[name='password']", "password123")
    page.click("button[type='submit']")
    page.wait_for_timeout(1000)
    page.screenshot(path="/home/jules/verification/screenshots/09_tutor_dashboard.png")

    # 10. Login as Admin & View Reports
    page.goto("http://localhost:8000/login.php")
    page.fill("input[name='email']", "admin@tutorlink.com")
    page.fill("input[name='password']", "password123")
    page.click("button[type='submit']")
    page.wait_for_timeout(1000)
    page.screenshot(path="/home/jules/verification/screenshots/10_admin_dashboard.png")

    page.goto("http://localhost:8000/admin/reports.php")
    page.wait_for_timeout(1000)
    page.screenshot(path="/home/jules/verification/screenshots/11_admin_reports.png")

if __name__ == "__main__":
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(
            record_video_dir="/home/jules/verification/videos"
        )
        page = context.new_page()
        try:
            run_cuj(page)
        finally:
            context.close()
            browser.close()
