import os
import pymysql
from dotenv import load_dotenv

class DatabaseRepository:
    def __init__(self):

        env_path = os.path.join(os.path.dirname(__file__), '..', '.env')
        load_dotenv(env_path)
        self.host = os.environ.get('DB_HOST', '127.0.0.1')
        self.port = int(os.environ.get('DB_PORT', 3306))
        self.user = os.environ.get('DB_USERNAME', 'root')
        self.password = os.environ.get('DB_PASSWORD', '')
        self.db = os.environ.get('DB_DATABASE', 'automation_db')

    def get_connection(self):
        return pymysql.connect(
            host=self.host,
            port=self.port,
            user=self.user,
            password=self.password,
            database=self.db,
            cursorclass=pymysql.cursors.DictCursor
        )

    def get_course(self, course_id: int):
        with self.get_connection() as conn:
            with conn.cursor() as cursor:
                sql = "SELECT * FROM courses WHERE id = %s"
                cursor.execute(sql, (course_id,))
                return cursor.fetchone()

    def get_questions(self, course_id: int):
        with self.get_connection() as conn:
            with conn.cursor() as cursor:
                sql = "SELECT * FROM questions WHERE course_id = %s ORDER BY sort_order ASC, created_at DESC"
                cursor.execute(sql, (course_id,))
                return cursor.fetchall()

    def find_question(self, course_id: int, normalized_question: str):
        with self.get_connection() as conn:
            with conn.cursor() as cursor:
                sql = "SELECT * FROM questions WHERE course_id = %s AND normalized_question = %s LIMIT 1"
                cursor.execute(sql, (course_id, normalized_question))
                return cursor.fetchone()

    def get_correct_answer(self, question_id: int):
        with self.get_connection() as conn:
            with conn.cursor() as cursor:
                sql = "SELECT * FROM question_options WHERE question_id = %s AND is_correct = 1 ORDER BY sort_order ASC"
                cursor.execute(sql, (question_id,))
                return cursor.fetchall()
