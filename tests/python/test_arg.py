import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[2]))
from Utils.arg import create_parser


class ArgPhp8Test(unittest.TestCase):
    def test_accepts_php8(self):
        args = create_parser().parse_args(["target", "--php_ver", "8"])
        self.assertEqual(args.php_ver, 8)

    def test_rejects_php9(self):
        with self.assertRaises(SystemExit):
            create_parser().parse_args(["target", "--php_ver", "9"])

    def test_defaults_stay_backwards_compatible(self):
        args = create_parser().parse_args(["target"])
        self.assertEqual(args.php_ver, 5)
        self.assertEqual(args.hook_extension, "uopz")


if __name__ == "__main__":
    unittest.main()
