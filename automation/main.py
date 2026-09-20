import time
import sys
import os

# Ensure the root directory is in the PYTHONPATH
sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), '..')))

from automation.database.db import Database
from automation.logging.logger import Logger
from automation.browser.browser_manager import BrowserManager

def get_pending_job():
    conn = Database.get_connection()
    try:
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT * FROM automation_jobs WHERE status = 'pending' ORDER BY created_at ASC LIMIT 1")
        return cursor.fetchone()
    finally:
        conn.close()

def update_job_status(job_id, status, current_step=None, error_message=None):
    conn = Database.get_connection()
    try:
        cursor = conn.cursor()
        if status == 'running':
            cursor.execute("UPDATE automation_jobs SET status=%s, current_step=%s, started_at=NOW() WHERE id=%s", (status, current_step, job_id))
        elif status in ('completed', 'failed', 'stopped'):
            cursor.execute("UPDATE automation_jobs SET status=%s, error_message=%s, completed_at=NOW() WHERE id=%s", (status, error_message, job_id))
        else:
            cursor.execute("UPDATE automation_jobs SET status=%s, current_step=%s WHERE id=%s", (status, current_step, job_id))
        conn.commit()
    finally:
        conn.close()

def process_job(job):
    job_id = job['id']
    job_type = job['job_type']

    Logger.info(job_id, 'init', f"Starting job {job_id} of type {job_type}")
    update_job_status(job_id, 'running', current_step='init')

    try:
        if job_type == 'test_browser':
            update_job_status(job_id, 'running', current_step='browser')
            Logger.info(job_id, 'browser', "Launching browser for testing")

            with BrowserManager() as browser:
                page = browser.page
                page.goto("https://example.com")
                title = page.title()
                Logger.info(job_id, 'browser', f"Opened test page. Title: {title}")
                screenshot = browser.take_screenshot(job_id, 'test_page')
                Logger.info(job_id, 'browser', "Took screenshot", screenshot)

        elif job_type == 'fetch_reviews':
            Logger.info(job_id, 'reviews', "Fetching reviews... (Mock implementation)")
            time.sleep(2) # Mock wait
            Logger.info(job_id, 'reviews', "Mock fetch complete")
        else:
            raise ValueError(f"Unknown job type: {job_type}")

        update_job_status(job_id, 'completed')
        Logger.info(job_id, 'finish', f"Job {job_id} completed successfully")

    except Exception as e:
        Logger.error(job_id, 'error', f"Job failed: {str(e)}")
        update_job_status(job_id, 'failed', error_message=str(e))


def main():
    print("Automation Worker Started")
    while True:
        try:
            job = get_pending_job()
            if job:
                process_job(job)
            else:
                time.sleep(5)  # Poll every 5 seconds
        except Exception as e:
            print(f"Worker Error: {e}")
            time.sleep(10)

if __name__ == "__main__":
    main()
