import datetime
from automation.database.db import Database

class Logger:
    @staticmethod
    def log(job_id: int, level: str, step: str, message: str, screenshot_path: str = None):
        print(f"[{datetime.datetime.now().strftime('%H:%M:%S')}] {level.upper()} JOB={job_id} STEP={step} {message}")
        conn = Database.get_connection()
        try:
            cursor = conn.cursor()
            query = "INSERT INTO automation_logs (job_id, level, step, message, screenshot_path) VALUES (%s, %s, %s, %s, %s)"
            cursor.execute(query, (job_id, level, step, message, screenshot_path))
            conn.commit()
        except Exception as e:
            print(f"Failed to log to database: {e}")
        finally:
            conn.close()

    @staticmethod
    def info(job_id: int, step: str, message: str, screenshot_path: str = None):
        Logger.log(job_id, 'info', step, message, screenshot_path)

    @staticmethod
    def warning(job_id: int, step: str, message: str, screenshot_path: str = None):
        Logger.log(job_id, 'warning', step, message, screenshot_path)

    @staticmethod
    def error(job_id: int, step: str, message: str, screenshot_path: str = None):
        Logger.log(job_id, 'error', step, message, screenshot_path)
