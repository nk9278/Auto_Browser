import sys
import os
import pytest

sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), '../..')))

from automation.browser.browser_manager import BrowserManager

def test_browser_launch_and_screenshot():
    with BrowserManager() as browser:
        page = browser.start()
        page.goto("https://example.com")
        assert "Example Domain" in page.title()

        screenshot_path = browser.take_screenshot("test_job", "test_step")
        assert os.path.exists(screenshot_path)
        os.remove(screenshot_path) # Clean up
