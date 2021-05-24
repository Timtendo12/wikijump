#
# messages.py - Wikijump Locale Builder
#

import re
from dataclasses import dataclass
from typing import Optional, Union

MESSAGE_FILENAME_REGEX = re.compile("([a-z]+)(?:_([A-Z]+))?\.ya?ml")

MessageData = dict[str, Union[str, 'MessageData']]

@dataclass
class Messages:
    language: str
    country: Optional[str]
    message_data: MessageData

    def get(self, path) -> str:
        data = self.message_data
        parts = path.split(".")

        for i, part in enumerate(parts):
            data = data.get(part)
            if data is None:
                raise KeyError(path)

            if isinstance(data, str):
                # Check that we're at the end of the path
                if i < len(parts) - 1:
                    raise KeyError(path)

                return data

        # Went through the entire path without finding the string
        raise KeyError(path)
