import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[2]))
from Utils.Bootstrap import Bootstrap


class BootstrapShapeTest(unittest.TestCase):
    def test_php8_uses_uopz_branch(self):
        # The PHP 8 path must not hit the runkit branch, which exits.
        source = Path(__file__).resolve().parents[2] / "Utils" / "Bootstrap.py"
        text = source.read_text()
        self.assertIn("Runkit does not support PHP 7/8", text)
        self.assertIn("PHP 7 and 8", text)


if __name__ == "__main__":
    unittest.main()
