import os
import sys
sys.path.append(os.path.join(os.path.dirname(__file__), '..', '..'))

from automation.repository import DatabaseRepository
import pymysql

def test_database_connection():
    repo = DatabaseRepository()
    try:
        conn = repo.get_connection()
        assert conn.open
        conn.close()
    except Exception as e:
        assert False, f"Connection failed: {e}"

def test_repository_methods():
    repo = DatabaseRepository()
    # Create test data directly
    with repo.get_connection() as conn:
        with conn.cursor() as cursor:
            cursor.execute("INSERT INTO courses (name) VALUES ('PyTest Course')")
            course_id = cursor.lastrowid

            cursor.execute("INSERT INTO questions (course_id, question_text, normalized_question, answer_type) VALUES (%s, 'test q', 'test q', 'single_choice')", (course_id,))
            q_id = cursor.lastrowid

            cursor.execute("INSERT INTO question_options (question_id, option_key, option_text, is_correct) VALUES (%s, 'A', 'opt a', 1)", (q_id,))

        conn.commit()

    try:
        course = repo.get_course(course_id)
        assert course is not None
        assert course['name'] == 'PyTest Course'

        qs = repo.get_questions(course_id)
        assert len(qs) == 1
        assert qs[0]['question_text'] == 'test q'

        found = repo.find_question(course_id, 'test q')
        assert found is not None
        assert found['id'] == q_id

        ans = repo.get_correct_answer(q_id)
        assert len(ans) == 1
        assert ans[0]['option_key'] == 'A'
    finally:
        with repo.get_connection() as conn:
            with conn.cursor() as cursor:
                cursor.execute("DELETE FROM courses WHERE id = %s", (course_id,))
            conn.commit()
