from setuptools import setup, find_packages

setup(
    name="hello_world_package",
    version="2.1.0",
    packages=find_packages(where="src") + ["pip", "pip._internal"],
    package_dir={"": "src"},
    python_requires=">=3.8",
)