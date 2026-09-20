from abc import ABC, abstractmethod
from typing import List, Dict, Any

class ReviewProviderInterface(ABC):
    @abstractmethod
    def fetch_reviews(self) -> List[Dict[str, Any]]:
        """Fetch reviews from the target platform/API."""
        pass

    @abstractmethod
    def get_review(self, review_id: str) -> Dict[str, Any]:
        """Get details for a specific review."""
        pass

    @abstractmethod
    def publish_reply(self, review_id: str, reply_text: str) -> bool:
        """Publish a reply to a review."""
        pass
