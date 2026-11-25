import sys
import types
from types import SimpleNamespace
import unittest

# Keep the unit tests offline: the production modules import these at module
# load time, but the functions under test never open a connection.
pika = types.ModuleType("pika")
pika.PlainCredentials = object
pika.BlockingConnection = object
pika.ConnectionParameters = object
pika.exceptions = SimpleNamespace(StreamLostError=Exception)
sys.modules.setdefault("pika", pika)

requests = types.ModuleType("requests")
requests.get = lambda *a, **k: None
requests.post = lambda *a, **k: None
sys.modules.setdefault("requests", requests)

dateutil = types.ModuleType("dateutil")
relativedelta_mod = types.ModuleType("dateutil.relativedelta")
relativedelta_mod.relativedelta = object
dateutil.relativedelta = relativedelta_mod
sys.modules.setdefault("dateutil", dateutil)
sys.modules.setdefault("dateutil.relativedelta", relativedelta_mod)

from Analyzer.dynamic import DynamicAnalyzer
from Analyzer.parser import ASTParser
from Analyzer.analyzer import Analyzer
from Analyzer.chain import ChainAnalyzer
from Analyzer.static import StaticAnalyzer
from Detector.DetectorManager import DetectorManager


class AnalyzerUnitTest(unittest.TestCase):
    def test_dynamic_type_and_visibility(self):
        d = DynamicAnalyzer()
        self.assertEqual([1], d._get_type(1))
        self.assertEqual("public", d._get_visibility(1))
        self.assertEqual("protected", d._get_visibility(2))
        self.assertEqual("private", d._get_visibility(4))
        self.assertEqual("public", d._get_visibility(0))

    def test_parser_type_static_visibility(self):
        p = ASTParser.__new__(ASTParser)
        self.assertEqual([1], p._get_type(1))
        self.assertTrue(p._get_static(8))
        self.assertFalse(p._get_static(1))
        self.assertEqual("private", p._get_visibility(4))

    def test_param_range_and_visibility(self):
        a = Analyzer.__new__(Analyzer)
        self.assertEqual(range(1, 3), a._get_param_cnt_range({"a": None, "b": "x"}))
        prop = SimpleNamespace(visibility="private")
        self.assertEqual("private", a._get_visibility({"p": prop}, "p"))
        self.assertEqual("public", a._get_visibility({}, "p"))

    def test_prop_method_list(self):
        a = Analyzer.__new__(Analyzer)
        props, methods = a._get_prop_method_list(
            {"p": {"candidates": [{"prop": "x"}, {"method": "m"}]}}, "p"
        )
        self.assertEqual(["x"], props)
        self.assertEqual(["m"], methods)

    def test_find_prop_and_method(self):
        a = Analyzer.__new__(Analyzer)
        prop = SimpleNamespace(visibility="protected")
        method = SimpleNamespace(visibility="public", param_list={"a": None})
        klass = SimpleNamespace(
            prop_list={"p": prop},
            method_list={"m": method},
            type=[],
        )
        file_obj = SimpleNamespace(class_list={"A": klass})
        a.target_file_list = {"f.php": file_obj}
        classes = {"A": {"FILE_NAME": "f.php"}}
        self.assertEqual(
            [{"file": "f.php", "class": "A", "visibility": "protected"}],
            a._find_prop(classes, "p"),
        )
        self.assertEqual(
            [{"file": "f.php", "class": "A", "visibility": "public"}],
            a._find_method(classes, "m", 1),
        )


    def test_global_vars_and_constants(self):
        a = Analyzer.__new__(Analyzer)
        code = a.get_global_vars({
            "a": None,
            "b": "x'y",
            "c": [1, "z"],
            "d": {"k": 2},
            "bad-key": 3,
        })
        self.assertIn("$a = NULL;", code)
        self.assertIn("\\'", code)
        self.assertIn("$bad_key = 3;", code)
        consts = a.get_constants({"A": "v'x", "B": None, "STDOUT": "skip"})
        self.assertIn("define('A', 'v\\'x');", consts)
        self.assertIn("define('B', NULL);", consts)
        self.assertNotIn("STDOUT", consts)
        self.assertTrue(a._encode_php("echo 1;").startswith(b"<?php"))

    def test_static_update_info(self):
        s = StaticAnalyzer.__new__(StaticAnalyzer)
        func_info = SimpleNamespace()
        class_info = SimpleNamespace(
            parents=[], implements=[], traits=[], method_list={},
            prop_list={}, type=[]
        )
        file_info = SimpleNamespace(
            func_list={"f": func_info}, class_list={"A": class_info}
        )
        target_class, target_func = s.update_info(
            {}, {}, {"A": {"INTERNAL": False}}, {}, {}, {"f.php": file_info}
        )
        self.assertIn("A", target_class)
        self.assertIn("f", target_func)

    def test_chain_filter_abstract(self):
        c = ChainAnalyzer.__new__(ChainAnalyzer)
        c.target_file_list = {
            "f.php": SimpleNamespace(
                class_list={"A": SimpleNamespace(type=[16])}
            )
        }
        chain = [
            {"file": "f.php", "class": "A"},
            {"file": "f.php", "class": "A"},
        ]
        self.assertFalse(c.filter_abstract_class(chain))

    def test_detector_base64(self):
        d = DetectorManager.__new__(DetectorManager)
        self.assertTrue(d._isB64Encoded("YWJj"))
        self.assertFalse(d._isB64Encoded("not base64!"))
        self.assertEqual("abc", d._B64Decode("YWJj"))
        self.assertFalse(d._B64Decode("!!!!"))


if __name__ == "__main__":
    unittest.main()
