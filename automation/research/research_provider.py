from abc import ABC, abstractmethod
from typing import List, Dict, Any

class ResearchProvider(ABC):
    @abstractmethod
    def search(self, query: str) -> List[Dict[str, Any]]:
        """Search public sources and retrieve relevant information with source URLs."""
        pass
