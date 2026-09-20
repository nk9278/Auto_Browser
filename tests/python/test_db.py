import sys
import os
import pytest

sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), '../..')))

from automation.database.db import Database

def test_db_connection():
    conn = Database.get_connection()
    assert conn.is_connected()

    cursor = conn.cursor()
    cursor.execute("SHOW TABLES")
    tables = [t[0] for t in cursor.fetchall()]
    assert 'automation_jobs' in tables
    conn.close()
