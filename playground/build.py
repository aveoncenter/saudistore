"""Regenerate blueprint.json from demo-setup.txt:  python3 playground/build.py [branch]"""
import json, os, sys
here = os.path.dirname(os.path.abspath(__file__))
branch = sys.argv[1] if len(sys.argv) > 1 else "main"
code = open(os.path.join(here, "demo-setup.txt"), encoding="utf-8").read()
bp = {
    "$schema": "https://playground.wordpress.net/blueprint-schema.json",
    "meta": {"title": "Saudistore demo", "description": "Saudistore theme + WooCommerce with demo products", "author": "aveoncenter"},
    "landingPage": "/product/linkedin-premium/",
    "preferredVersions": {"php": "8.2", "wp": "latest"},
    "features": {"networking": True},
    "login": True,
    "steps": [
        {"step": "installPlugin", "pluginData": {"resource": "wordpress.org/plugins", "slug": "woocommerce"}, "options": {"activate": True}},
        {"step": "installTheme", "themeData": {"resource": "git:directory", "url": "https://github.com/aveoncenter/saudistore", "ref": branch, "refType": "branch"}, "options": {"activate": True, "targetFolderName": "saudistore"}},
        {"step": "setSiteLanguage", "language": "ar"},
        {"step": "runPHP", "code": code},
    ],
}
with open(os.path.join(here, "blueprint.json"), "w", encoding="utf-8") as f:
    json.dump(bp, f, ensure_ascii=False, indent=2)
print("blueprint.json ->", branch)
