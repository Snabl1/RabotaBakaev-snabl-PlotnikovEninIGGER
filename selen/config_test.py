# Конфигурация тестирования (редактируй под свой стенд)
# Переменные окружения: BASE_URL, LOGIN_USER, LOGIN_PASS, MYSQL_*, DO_BACKUP и др.

import os

# URL сайта (без слэша в конце)
BASE_URL = os.environ.get("BASE_URL", "http://localhost:3000")

# Учётные данные админа
LOGIN_USER = os.environ.get("LOGIN_USER", "admin")
LOGIN_PASS = os.environ.get("LOGIN_PASS", "admin123")

# Корень проекта (где лежат .php и config.php)
PROJECT_ROOT = os.environ.get("PROJECT_ROOT") or os.path.normpath(
    os.path.join(os.path.dirname(__file__), "..")
)

# Бекап перед тестами
DO_BACKUP = os.environ.get("DO_BACKUP", "").lower() in ("1", "true", "yes")

# БД для проверки на пустоты
MYSQL_HOST = os.environ.get("MYSQL_HOST", "")
MYSQL_PORT = os.environ.get("MYSQL_PORT", "3306")
MYSQL_DB   = os.environ.get("MYSQL_DB", "project_Bakaev")
MYSQL_USER = os.environ.get("MYSQL_USER", "")
MYSQL_PASS = os.environ.get("MYSQL_PASS", "")

# Загрузочное тестирование (п.6): уровни одновременных пользователей и запросов на уровень
LOAD_TEST_CONCURRENT_LEVELS = [1, 5, 10, 20, 30, 50]
LOAD_TEST_REQUESTS_PER_LEVEL = 30

# Тест сети (п.7): количество запросов для замера качества
NETWORK_TEST_REQUESTS = 50
