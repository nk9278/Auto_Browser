import os
import time
from playwright.sync_api import sync_playwright, Page, Browser, BrowserContext
from typing import Optional

class BrowserManager:
    def __init__(self):
        self.headless = os.getenv('BROWSER_HEADLESS', 'false').lower() == 'true'
        self.timeout = int(os.getenv('BROWSER_TIMEOUT', '30000'))
        self.playwright = None
        self.browser: Optional[Browser] = None
        self.context: Optional[BrowserContext] = None
        self.page: Optional[Page] = None

    def start(self) -> Page:
        if not self.playwright:
            self.playwright = sync_playwright().start()

        if not self.browser:
            self.browser = self.playwright.chromium.launch(
                headless=self.headless,
                args=['--no-sandbox', '--disable-setuid-sandbox']
            )

        if not self.context:
            self.context = self.browser.new_context(
                viewport={'width': 1280, 'height': 720}
            )
            self.context.set_default_timeout(self.timeout)

        if not self.page:
            self.page = self.context.new_page()

        return self.page

    def take_screenshot(self, job_id: str, step_name: str) -> str:
        if not self.page:
            raise Exception("Cannot take screenshot, page is not initialized.")

        os.makedirs('storage/screenshots', exist_ok=True)
        timestamp = time.strftime("%Y%m%d_%H%M%S")
        filename = f"job_{job_id}_{timestamp}_{step_name}.png"
        filepath = os.path.join('storage/screenshots', filename)

        self.page.screenshot(path=filepath)
        return filepath

    def close(self):
        if self.page:
            self.page.close()
            self.page = None
        if self.context:
            self.context.close()
            self.context = None
        if self.browser:
            self.browser.close()
            self.browser = None
        if self.playwright:
            self.playwright.stop()
            self.playwright = None

    def __enter__(self):
        self.start()
        return self

    def __exit__(self, exc_type, exc_val, exc_tb):
        self.close()
