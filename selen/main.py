# -*- coding: utf-8 -*-
"""
Сценарий тестирования сайта «Танковая База» (PHP + Selenium).
По t.txt:
  1. Логин и пароль
  2. Проверка соответствия кода стандартам (php -l)
  3. Проверка БД на пустоты
  4. Бекапирование
  5. Нагрузочная статистика
  6. Загрузочное тестирование (нагрузка, график)
  7. Тестирование сети (пакеты, скорость)
"""
import time
import random
import os
import sys

# При запуске из PHP (Windows) stdout может быть cp1251 — эмодзи вызывают UnicodeEncodeError
if hasattr(sys.stdout, "reconfigure"):
    try:
        sys.stdout.reconfigure(encoding="utf-8", errors="replace")
        sys.stderr.reconfigure(encoding="utf-8", errors="replace")
    except Exception:
        pass

import subprocess
import shutil
from datetime import datetime
from concurrent.futures import ThreadPoolExecutor, as_completed
from urllib.request import Request, urlopen

from selenium import webdriver
from selenium.webdriver.chrome.service import Service as ChromeService
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import Select, WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException
from rich.console import Console
from rich.table import Table
from rich.progress import Progress, SpinnerColumn, TextColumn
from rich.panel import Panel
from rich.layout import Layout
from rich import box

# Конфиг тестов (URL, логин, БД, бекап)
try:
    import config_test as cfg
except ImportError:
    cfg = type(sys)("config_test")
    cfg.BASE_URL = os.environ.get("BASE_URL", "http://localhost:3000")
    cfg.LOGIN_USER = os.environ.get("LOGIN_USER", "admin")
    cfg.LOGIN_PASS = os.environ.get("LOGIN_PASS", "admin123")
    cfg.PROJECT_ROOT = os.path.normpath(os.path.join(os.path.dirname(__file__), ".."))
    cfg.DO_BACKUP = os.environ.get("DO_BACKUP", "").lower() in ("1", "true", "yes")
    cfg.MYSQL_HOST = os.environ.get("MYSQL_HOST", "")
    cfg.MYSQL_DB = os.environ.get("MYSQL_DB", "project_Bakaev")
    cfg.MYSQL_USER = os.environ.get("MYSQL_USER", "")
    cfg.MYSQL_PASS = os.environ.get("MYSQL_PASS", "")

console = Console()


# ---------- 1. Логин и пароль (Selenium) ----------
# ---------- 2. Проверка кода (php -l) ----------
# ---------- 3. Проверка БД на пустоты ----------
# ---------- 4. Бекапирование ----------
# ---------- 5. Нагрузочная статистика ----------

# Данные для веб-отчёта (заполняются в п.6 и п.7, пишутся в finalize)
_report_load_graph_path = None
_report_load_stats = None  # list of {concurrent, avg_ms, success_rate}
_report_network_graph_path = None
_report_network_stats = None

def run_code_standards_check():
    """Проверка PHP: синтаксис (php -l) и соответствие PSR-1/PSR-2 (phpcs при наличии)."""
    root = cfg.PROJECT_ROOT
    # 1) Синтаксис php -l
    console.print("\n[bold cyan]📐 Проверка соответствия кода стандартам (php -l, PSR-1/PSR-2)...[/bold cyan]")
    errors = []
    checked = 0
    for dirpath, _, filenames in os.walk(root):
        if "selen" in dirpath or "node_modules" in dirpath or "vendor" in dirpath:
            continue
        for f in filenames:
            if not f.endswith(".php"):
                continue
            path = os.path.join(dirpath, f)
            rel = os.path.relpath(path, root)
            try:
                r = subprocess.run(
                    ["php", "-l", path],
                    capture_output=True,
                    text=True,
                    timeout=5,
                    cwd=root,
                )
                checked += 1
                if r.returncode != 0:
                    errors.append((rel, r.stderr or r.stdout or "ошибка синтаксиса"))
            except FileNotFoundError:
                console.print("[yellow]⚠ PHP не найден в PATH, проверка пропущена[/yellow]")
                return
            except Exception as e:
                errors.append((rel, str(e)))
    if errors:
        for rel, msg in errors:
            console.print(f"[red]✗ {rel}:[/red] {msg.strip()[:200]}")
        console.print(f"[red]Всего ошибок: {len(errors)} из {checked} файлов[/red]")
        return False
    console.print(f"[green]✓ Проверено {checked} PHP-файлов, синтаксис в порядке[/green]")
    # 2) PSR-1 / PSR-2 через phpcs (если установлен)
    try:
        r = subprocess.run(
            ["phpcs", "--standard=PSR2", "-n", "--extensions=php", "--ignore=selen,vendor,node_modules", root],
            capture_output=True,
            text=True,
            timeout=60,
            cwd=root,
        )
        if r.returncode == 0:
            console.print("[green]✓ Соответствие PSR-1/PSR-2 (phpcs): нарушений не найдено[/green]")
        else:
            out = (r.stdout or "") + (r.stderr or "")
            console.print("[yellow]⚠ PSR-1/PSR-2 (phpcs):[/yellow]")
            for line in out.strip().split("\n")[-15:]:
                console.print(f"[dim]{line}[/dim]")
    except FileNotFoundError:
        console.print("[dim]phpcs не установлен — для проверки PSR-1/PSR-2: composer require squizlabs/php_codesniffer[/dim]")
    except Exception as e:
        console.print(f"[dim]phpcs: {e}[/dim]")
    return True


def run_db_empty_check():
    """Проверка БД на критические пустоты (NULL/пустые обязательные поля)."""
    console.print("\n[bold cyan]🗄 Проверка БД на пустоты...[/bold cyan]")
    if not cfg.MYSQL_HOST or not cfg.MYSQL_USER:
        console.print("[dim]MYSQL_HOST/MYSQL_USER не заданы — проверка БД пропущена[/dim]")
        return True
    try:
        import pymysql
    except ImportError:
        console.print("[yellow]⚠ Установите pymysql: pip install pymysql[/yellow]")
        return True
    try:
        port = int(os.environ.get("MYSQL_PORT", "3306"))
        if ":" in cfg.MYSQL_HOST:
            host, port_str = cfg.MYSQL_HOST.rsplit(":", 1)
            port = int(port_str)
        else:
            host = cfg.MYSQL_HOST
        conn = pymysql.connect(
            host=host,
            port=port,
            user=cfg.MYSQL_USER,
            password=cfg.MYSQL_PASS,
            database=cfg.MYSQL_DB,
            charset="utf8mb4",
        )
    except Exception as e:
        console.print(f"[red]✗ Подключение к БД: {e}[/red]")
        return False
    problems = []
    try:
        with conn.cursor() as cur:
            # Таблицы и поля, которые не должны быть NULL/пустыми (для ключевых записей)
            checks = [
                ("clients", "client_id", "last_name", "first_name"),
                ("components", "component_id", "component_name"),
                ("orders", "order_id", "total_cost"),
                ("component_categories", "category_id", "category_name"),
            ]
            for table, *cols in checks:
                try:
                    for col in cols:
                        cur.execute(
                            f"SELECT COUNT(*) FROM `{table}` WHERE `{col}` IS NULL OR TRIM(COALESCE(`{col}`,'')) = ''"
                        )
                        (empty_count,) = cur.fetchone()
                        if empty_count > 0:
                            problems.append(f"{table}.{col}: {empty_count} пустых")
                except Exception as e:
                    problems.append(f"{table}: {e}")
    finally:
        conn.close()
    if problems:
        for p in problems:
            console.print(f"[red]✗ {p}[/red]")
        return False
    console.print("[green]✓ Критических пустот в БД не обнаружено[/green]")
    return True


def run_backup():
    """Бекап важных файлов проекта в backup/."""
    console.print("\n[bold cyan]💾 Бекапирование...[/bold cyan]")
    root = cfg.PROJECT_ROOT
    backup_dir = os.path.join(root, "backup")
    timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    target = os.path.join(backup_dir, timestamp)
    os.makedirs(target, exist_ok=True)
    to_copy = []
    for name in os.listdir(root):
        if name in ("selen", "backup", "node_modules", ".git", "vendor"):
            continue
        path = os.path.join(root, name)
        if os.path.isfile(path) and (name.endswith(".php") or name.endswith(".css")):
            to_copy.append((path, name))
        elif os.path.isdir(path) and name not in ("screenshots", "backup"):
            for sub in os.listdir(path):
                if sub.endswith(".php"):
                    to_copy.append((os.path.join(path, sub), os.path.join(name, sub)))
    for src, rel in to_copy[:100]:  # ограничение
        if os.path.isfile(src):
            dest = os.path.join(target, rel)
            os.makedirs(os.path.dirname(dest), exist_ok=True)
            try:
                shutil.copy2(src, dest)
            except Exception as e:
                console.print(f"[yellow]⚠ Не скопирован {rel}: {e}[/yellow]")
    console.print(f"[green]✓ Бекап создан: {target}[/green]")
    return True


def _http_request(url, timeout=5):
    """Один HTTP-запрос, возвращает (время_сек, успех, размер_ответа). Таймаут 5 с — чтобы не зависать при недоступном сервере."""
    start = time.perf_counter()
    try:
        req = Request(url, headers={"User-Agent": "TankBaseTest/1.0"})
        with urlopen(req, timeout=timeout) as r:
            data = r.read()
        return time.perf_counter() - start, True, len(data)
    except Exception:
        return time.perf_counter() - start, False, 0


def is_server_reachable(base_url, timeout=2):
    """Быстрая проверка: доступен ли сервер (чтобы не зависать и не сыпать ошибки Selenium)."""
    base = base_url.rstrip("/")
    url = base + "/index.php"
    try:
        req = Request(url, headers={"User-Agent": "TankBaseTest/1.0"})
        urlopen(req, timeout=timeout)
        return True
    except Exception:
        return False


def run_load_stats(base_url, num_requests=30, num_workers=5):
    """5. Нагрузочная статистика: RPS и время ответа."""
    console.print("\n[bold cyan]📈 Нагрузочная статистика (п.5)...[/bold cyan]")
    if not is_server_reachable(base_url):
        console.print("[yellow]Сервер недоступен — нагрузочная статистика пропущена.[/yellow]")
        return
    base = base_url.rstrip("/")
    urls = [base + "/index.php", base + "/login.php", base + "/orders.php"]
    times = []
    start_total = time.perf_counter()
    timeout_total = num_requests * 6 + 30  # не ждать бесконечно при зависших запросах
    ex = ThreadPoolExecutor(max_workers=num_workers)
    futures = [ex.submit(_http_request, random.choice(urls)) for _ in range(num_requests)]
    try:
        for f in as_completed(futures, timeout=timeout_total):
            t, ok, _ = f.result()
            times.append((t, ok))
    except (KeyboardInterrupt, TimeoutError):
        for fut in futures:
            fut.cancel()
        ex.shutdown(wait=False)
        console.print("[yellow]Нагрузочная статистика прервана.[/yellow]")
        return
    finally:
        ex.shutdown(wait=True)
    total_time = time.perf_counter() - start_total
    ok_times = [t for t, ok in times if ok]
    success = len(ok_times)
    if not times:
        console.print("[red]Нет замеров[/red]")
        return
    console.print(
        f"[green]Запросов: {num_requests}, успешно: {success}, "
        f"время (с): {total_time:.2f}, RPS: {num_requests / total_time:.1f}[/green]"
    )
    if ok_times:
        console.print(
            f"[dim]Время ответа (с): мин {min(ok_times):.3f}, макс {max(ok_times):.3f}, "
            f"средн {sum(ok_times) / len(ok_times):.3f}[/dim]"
        )
    return True


def run_load_test_with_graph(base_url):
    """6. Загрузочное тестирование: сколько пользователей без лагов, график."""
    console.print("\n[bold cyan]🔥 Загрузочное тестирование (п.6)...[/bold cyan]")
    if not is_server_reachable(base_url):
        console.print("[yellow]Сервер недоступен — загрузочный тест пропущен.[/yellow]")
        return True
    base = base_url.rstrip("/")
    url = base + "/index.php"
    levels = getattr(cfg, "LOAD_TEST_CONCURRENT_LEVELS", [1, 5, 10, 20, 30, 50])
    per_level = getattr(cfg, "LOAD_TEST_REQUESTS_PER_LEVEL", 30)
    results = []  # (concurrent, avg_time_ms, success_rate)
    timeout_per_level = per_level * 6 + 30
    for n in levels:
        console.print(f"  [dim]Нагрузка: {n} одновременных пользователей...[/dim]")
        start = time.perf_counter()
        times_ok = []
        ex = ThreadPoolExecutor(max_workers=n)
        futures = [ex.submit(_http_request, url) for _ in range(per_level)]
        try:
            for f in as_completed(futures, timeout=timeout_per_level):
                t, ok, _ = f.result()
                if ok:
                    times_ok.append(t)
        except (KeyboardInterrupt, TimeoutError):
            for fut in futures:
                fut.cancel()
            console.print("[yellow]Загрузочный тест прерван.[/yellow]")
            return True
        finally:
            ex.shutdown(wait=True)
        elapsed = time.perf_counter() - start
        success_rate = (len(times_ok) / per_level * 100) if per_level else 0
        avg_ms = (sum(times_ok) / len(times_ok) * 1000) if times_ok else 0
        results.append((n, avg_ms, success_rate))
        console.print(f"    [green]Среднее время: {avg_ms:.0f} мс, успех: {success_rate:.1f}%[/green]")
    # График
    try:
        import matplotlib
        matplotlib.use("Agg")
        import matplotlib.pyplot as plt
        fig, (ax1, ax2) = plt.subplots(2, 1, figsize=(10, 8))
        xs = [r[0] for r in results]
        ax1.plot(xs, [r[1] for r in results], "o-", color="#00aa44", linewidth=2, markersize=8)
        ax1.set_ylabel("Среднее время ответа (мс)")
        ax1.set_xlabel("Одновременных пользователей")
        ax1.set_title("Загрузочное тестирование: время ответа под нагрузкой")
        ax1.grid(True, alpha=0.3)
        ax2.bar([str(x) for x in xs], [r[2] for r in results], color="#0088cc", alpha=0.8)
        ax2.set_ylabel("Успешность (%)")
        ax2.set_xlabel("Одновременных пользователей")
        ax2.set_title("Доля успешных запросов")
        ax2.set_ylim(0, 105)
        plt.tight_layout()
        report_dir = os.path.join(os.path.dirname(__file__), "reports")
        os.makedirs(report_dir, exist_ok=True)
        path = os.path.join(report_dir, f"load_test_{datetime.now().strftime('%Y%m%d_%H%M%S')}.png")
        plt.savefig(path, dpi=120)
        plt.close()
        global _report_load_graph_path, _report_load_stats
        _report_load_graph_path = path
        _report_load_stats = [{"concurrent": r[0], "avg_ms": r[1], "success_rate": r[2]} for r in results]
        console.print(f"[green]✓ График сохранён: {path}[/green]")
    except ImportError:
        console.print("[yellow]⚠ matplotlib не установлен — график не построен (pip install matplotlib)[/yellow]")
    return True


def run_network_quality_test(base_url):
    """7. Тестирование сети: пакеты, скорость, качество соединения."""
    console.print("\n[bold cyan]🌐 Тестирование сети (п.7)...[/bold cyan]")
    if not is_server_reachable(base_url):
        console.print("[yellow]Сервер недоступен — тест сети пропущен.[/yellow]")
        return True
    base = base_url.rstrip("/")
    url = base + "/index.php"
    n = getattr(cfg, "NETWORK_TEST_REQUESTS", 50)
    workers = min(10, n)
    latencies = []
    total_bytes = 0
    timeout_total = n * 6 + 30
    start = time.perf_counter()
    ex = ThreadPoolExecutor(max_workers=workers)
    futures = [ex.submit(_http_request, url) for _ in range(n)]
    try:
        for f in as_completed(futures, timeout=timeout_total):
            t, ok, size = f.result()
            latencies.append((t * 1000, ok))
            if ok:
                total_bytes += size
    except (KeyboardInterrupt, TimeoutError):
        for fut in futures:
            fut.cancel()
        console.print("[yellow]Тест сети прерван.[/yellow]")
        return True
    finally:
        ex.shutdown(wait=True)
    elapsed = time.perf_counter() - start
    ok_latencies = [lat for lat, ok in latencies if ok]
    success_rate = (len(ok_latencies) / n * 100) if n else 0
    packet_loss = 100 - success_rate
    if ok_latencies:
        avg_lat = sum(ok_latencies) / len(ok_latencies)
        min_lat = min(ok_latencies)
        max_lat = max(ok_latencies)
        sorted_lat = sorted(ok_latencies)
        p95 = sorted_lat[int(len(sorted_lat) * 0.95)] if sorted_lat else 0
    else:
        avg_lat = min_lat = max_lat = p95 = 0
    speed_rps = n / elapsed if elapsed else 0
    speed_kbps = (total_bytes * 8 / 1024) / elapsed if elapsed else 0
    console.print(Panel(
        f"[bold]Запросов:[/bold] {n}\n"
        f"[bold]Успешно:[/bold] {len(ok_latencies)} | [bold]Потеря пакетов:[/bold] {packet_loss:.1f}%\n"
        f"[bold]Задержка (мс):[/bold] мин {min_lat:.0f} | макс {max_lat:.0f} | средн {avg_lat:.0f} | p95 {p95:.0f}\n"
        f"[bold]Скорость:[/bold] {speed_rps:.1f} запр/с | ~{speed_kbps:.1f} Кбит/с (down)",
        title="Качество сети",
        border_style="green",
    ))
    global _report_network_graph_path, _report_network_stats
    _report_network_stats = {
        "requests": n, "success_rate": success_rate, "packet_loss": packet_loss,
        "latency_min": min_lat, "latency_max": max_lat, "latency_avg": avg_lat, "latency_p95": p95,
        "rps": speed_rps, "kbps": speed_kbps,
    }
    # График распределения задержек
    try:
        import matplotlib
        matplotlib.use("Agg")
        import matplotlib.pyplot as plt
        if ok_latencies:
            fig, ax = plt.subplots(figsize=(8, 4))
            ax.hist(ok_latencies, bins=min(20, len(ok_latencies)), color="#00aa44", alpha=0.7, edgecolor="black")
            ax.set_xlabel("Задержка (мс)")
            ax.set_ylabel("Количество")
            ax.set_title("Распределение задержек (тест сети)")
            ax.axvline(avg_lat, color="red", linestyle="--", label=f"Среднее {avg_lat:.0f} мс")
            ax.legend()
            ax.grid(True, alpha=0.3)
            report_dir = os.path.join(os.path.dirname(__file__), "reports")
            os.makedirs(report_dir, exist_ok=True)
            path = os.path.join(report_dir, f"network_{datetime.now().strftime('%Y%m%d_%H%M%S')}.png")
            plt.savefig(path, dpi=120)
            plt.close()
            _report_network_graph_path = path
            console.print(f"[green]✓ График задержек: {path}[/green]")
    except ImportError:
        pass
    return True


# ---------- Selenium-тестер ----------

class PHPSiteTester:
    def __init__(self, base_url=None):
        self.base_url = (base_url or cfg.BASE_URL).rstrip("/")
        self.driver = None
        self.results = []
        self.start_time = None
        self.console = console
        self.screenshot_dir = None
        self.test_counter = 1

    def setup(self):
        self.console.print("[bold cyan]🚀 Настройка тестовой среды...[/bold cyan]")
        timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
        self.screenshot_dir = os.path.join(os.path.dirname(__file__), "screenshots", timestamp)
        os.makedirs(self.screenshot_dir, exist_ok=True)
        options = webdriver.ChromeOptions()
        options.add_argument("--no-sandbox")
        options.add_argument("--disable-dev-shm-usage")
        options.add_argument("--start-maximized")
        options.add_argument("--disable-gpu")
        options.add_experimental_option("excludeSwitches", ["enable-logging"])
        try:
            try:
                from webdriver_manager.chrome import ChromeDriverManager
                service = ChromeService(ChromeDriverManager().install())
                self.driver = webdriver.Chrome(service=service, options=options)
            except ImportError:
                self.driver = webdriver.Chrome(options=options)
            self.driver.implicitly_wait(5)
            self.console.print("[green]✓ Драйвер Chrome запущен[/green]")
            return True
        except Exception as e:
            self.console.print(f"[red]✗ Ошибка драйвера: {e}[/red]")
            self.console.print("[dim]Установите: pip install webdriver-manager (автозагрузка ChromeDriver)[/dim]")
            return False

    def take_screenshot(self, test_name, description=""):
        try:
            safe_name = "".join(c if c.isalnum() else "_" for c in test_name)
            filename = f"{self.test_counter:02d}_{safe_name}_{datetime.now().strftime('%H%M%S')}.png"
            filepath = os.path.join(self.screenshot_dir, filename)
            self.driver.save_screenshot(filepath)
            self.test_counter += 1
            return {"path": filepath, "name": filename, "test": test_name, "description": description}
        except Exception as e:
            self.console.print(f"[yellow]⚠ Скриншот: {e}[/yellow]")
            return None

    def wait_for_element(self, selector, by=By.CSS_SELECTOR, timeout=10):
        try:
            return WebDriverWait(self.driver, timeout).until(
                EC.presence_of_element_located((by, selector))
            )
        except TimeoutException:
            return None

    def safe_send_keys(self, selector, text, by=By.CSS_SELECTOR):
        try:
            el = self.wait_for_element(selector, by)
            if el:
                el.clear()
                el.send_keys(text)
                return True
            return False
        except Exception as e:
            self.console.print(f"[yellow]⚠ Ошибка ввода: {e}[/yellow]")
            return False

    def record_result(self, test_name, success, details="", screenshot_info=None):
        status = "✅ УСПЕХ" if success else "❌ ОШИБКА"
        color = "green" if success else "red"
        det = details + (f" (скриншот: {screenshot_info['name']})" if screenshot_info else "")
        self.results.append({
            "test": test_name, "status": status, "details": det, "color": color,
            "screenshot": screenshot_info, "success": success,
        })
        self.console.print(f"[{color}]✓ {test_name}: {details}[/{color}]" if success else f"[{color}]✗ {test_name}: {details}[/{color}]")

    def test_login(self):
        """1. Логин и пароль — вход под админом."""
        self.console.print("\n[bold cyan]🔐 Тест: Логин и пароль...[/bold cyan]")
        try:
            self.driver.get(f"{self.base_url}/login.php")
            time.sleep(1)
            if not self.safe_send_keys("input[name='username']", cfg.LOGIN_USER):
                self.record_result("Логин", False, "Поле логина не найдено")
                return False
            if not self.safe_send_keys("input[name='password']", cfg.LOGIN_PASS):
                self.record_result("Логин", False, "Поле пароля не найдено")
                return False
            btn = self.wait_for_element("button[type='submit']") or self.wait_for_element("input[type='submit']")
            if not btn:
                self.record_result("Логин", False, "Кнопка входа не найдена")
                return False
            btn.click()
            time.sleep(2)
            scr = self.take_screenshot("Логин_после_входа", "После отправки формы")
            if "Выход" in self.driver.page_source or "index.php" in self.driver.current_url:
                self.record_result("Логин и пароль", True, "Вход выполнен", scr)
                return True
            if "Неверный" in self.driver.page_source or "ошибка" in self.driver.page_source.lower():
                self.record_result("Логин и пароль", False, "Неверный логин/пароль", scr)
                return False
            self.record_result("Логин и пароль", True, "Переход после входа", scr)
            return True
        except Exception as e:
            scr = self.take_screenshot("Логин_ошибка", str(e))
            self.record_result("Логин и пароль", False, str(e), scr)
            return False

    def test_navigation(self):
        self.console.print("\n[bold cyan]🧭 Навигация...[/bold cyan]")
        menu_items = [
            ("Заказы", "orders.php"),
            ("Клиенты", "clients.php"),
            ("Комплектующие", "components.php"),
            ("Категории", "categories.php"),
            ("Зоны доставки", "delivery_zones.php"),
            ("Гарантии", "warranties.php"),
        ]
        for name, url in menu_items:
            try:
                self.driver.get(f"{self.base_url}/{url}")
                time.sleep(2)
                scr = self.take_screenshot(f"Навигация_{name}", self.driver.title)
                if name.lower() in self.driver.title.lower() or name[:4].lower() in self.driver.find_element(By.TAG_NAME, "body").text.lower():
                    self.record_result(f"Навигация: {name}", True, self.driver.title, scr)
                else:
                    self.record_result(f"Навигация: {name}", False, "Контент не найден", scr)
            except Exception as e:
                scr = self.take_screenshot(f"Навигация_{name}_ERROR", str(e))
                self.record_result(f"Навигация: {name}", False, str(e), scr)

    def test_clients(self):
        """Клиенты — форма с логином, паролем, email (новый сценарий)."""
        self.console.print("\n[bold cyan]👥 Клиенты...[/bold cyan]")
        self.driver.get(f"{self.base_url}/clients.php")
        time.sleep(2)
        scr = self.take_screenshot("Клиенты_страница", "Страница клиентов")
        try:
            uname = f"testuser_{random.randint(1000,9999)}"
            self.safe_send_keys("input[name='username']", uname)
            self.safe_send_keys("input[name='email']", f"{uname}@test.local")
            self.safe_send_keys("input[name='password']", "testpass123")
            self.safe_send_keys("input[name='confirm_password']", "testpass123")
            self.safe_send_keys("input[name='last_name']", "Тестов")
            self.safe_send_keys("input[name='first_name']", "Клиент")
            self.safe_send_keys("input[name='address']", "ул. Тестовая, 1")
            add_btn = self.wait_for_element("button[name='add']")
            if add_btn:
                add_btn.click()
                time.sleep(2)
                scr2 = self.take_screenshot("Клиенты_после_добавления", "После отправки")
                if "успешно" in self.driver.page_source.lower():
                    self.record_result("Добавление клиента", True, "Клиент добавлен", scr2)
                else:
                    self.record_result("Добавление клиента", False, "Нет сообщения об успехе", scr2)
            else:
                self.record_result("Добавление клиента", False, "Кнопка добавления не найдена", scr)
        except Exception as e:
            self.record_result("Добавление клиента", False, str(e), self.take_screenshot("Клиенты_ошибка", str(e)))

    def test_orders(self):
        self.console.print("\n[bold cyan]📦 Заказы...[/bold cyan]")
        self.driver.get(f"{self.base_url}/orders.php")
        time.sleep(3)
        scr = self.take_screenshot("Заказы_страница", "Страница заказов")
        # У админа есть select client_id; у юзера — скрыто. Проверяем форму в целом
        form = self.wait_for_element("form#orderForm") or self.wait_for_element("form[method='POST']")
        grand = self.wait_for_element("#grandTotal")
        if form and grand:
            self.record_result("Форма заказа", True, "Форма и итог найдены", scr)
        else:
            self.record_result("Форма заказа", False, "Форма или #grandTotal не найдены", scr)

    def test_categories(self):
        self.console.print("\n[bold cyan]📂 Категории...[/bold cyan]")
        self.driver.get(f"{self.base_url}/categories.php")
        time.sleep(2)
        inp = self.wait_for_element("input[name='category_name']")
        if inp:
            inp.send_keys(f"ТестКат_{random.randint(1000,9999)}")
            btn = self.driver.find_element(By.CSS_SELECTOR, "button[name='add']")
            if btn:
                btn.click()
                time.sleep(2)
                scr = self.take_screenshot("Категории_добавление", "После добавления")
                self.record_result("Добавление категории", "успешно" in self.driver.page_source.lower(), "Проверка по тексту", scr)
        else:
            self.record_result("Категории", False, "Поле category_name не найдено")

    def test_components(self):
        self.console.print("\n[bold cyan]⚙️ Комплектующие...[/bold cyan]")
        self.driver.get(f"{self.base_url}/components.php")
        time.sleep(2)
        tbl = self.wait_for_element("table")
        scr = self.take_screenshot("Комплектующие", "Страница комплектующих")
        self.record_result("Комплектующие", tbl is not None, "Таблица найдена" if tbl else "Таблица не найдена", scr)

    def test_delivery_zones(self):
        self.console.print("\n[bold cyan]🗺️ Зоны доставки...[/bold cyan]")
        self.driver.get(f"{self.base_url}/delivery_zones.php")
        time.sleep(2)
        z = self.wait_for_element("input[name='zone_name']")
        scr = self.take_screenshot("Зоны_доставки", "Форма зон")
        self.record_result("Зоны доставки", z is not None, "Форма найдена" if z else "Форма не найдена", scr)

    def test_warranties(self):
        self.console.print("\n[bold cyan]🛡️ Гарантии...[/bold cyan]")
        self.driver.get(f"{self.base_url}/warranties.php")
        time.sleep(2)
        scr = self.take_screenshot("Гарантии", "Страница гарантий")
        self.record_result("Гарантии", "гарант" in self.driver.page_source.lower() or "warranty" in self.driver.page_source.lower(), "Страница загружена", scr)

    def run_all_tests(self):
        self.start_time = time.time()

        # 4. Бекап (если включён)
        if getattr(cfg, "DO_BACKUP", False):
            run_backup()

        # 2. Стандарты кода
        run_code_standards_check()

        # 3. БД на пустоты
        run_db_empty_check()

        # Проверка доступности сервера — без неё Selenium и нагрузочные тесты бессмысленны
        if not is_server_reachable(self.base_url):
            self.console.print(
                f"[red]Сервер недоступен ({self.base_url}). "
                "Запустите приложение (например: php -S localhost:3000 в корне проекта) и повторите.[/red]"
            )
            self.console.print("[dim]Проверки кода и БД выполнены. Selenium и тесты п.5–7 пропущены.[/dim]")
            self.finalize()
            return

        # Selenium
        if not self.setup():
            return
        try:
            if not self.test_login():
                self.console.print("[yellow]Логин не удался — часть тестов может падать (редирект на логин)[/yellow]")
            tests = [
                ("Навигация", self.test_navigation),
                ("Категории", self.test_categories),
                ("Клиенты", self.test_clients),
                ("Комплектующие", self.test_components),
                ("Заказы", self.test_orders),
                ("Зоны доставки", self.test_delivery_zones),
                ("Гарантии", self.test_warranties),
            ]
            with Progress(SpinnerColumn(), TextColumn("[progress.description]{task.description}"), console=self.console) as progress:
                task = progress.add_task("[cyan]Тесты...", total=len(tests))
                for name, func in tests:
                    progress.update(task, description=f"[cyan] {name}")
                    func()
                    progress.advance(task)
        except Exception as e:
            self.console.print(f"[red]Критическая ошибка: {e}[/red]")
        finally:
            # 5. Нагрузочная статистика
            run_load_stats(self.base_url)
            # 6. Загрузочное тестирование с графиком
            run_load_test_with_graph(self.base_url)
            # 7. Тестирование сети (качество и скорость)
            run_network_quality_test(self.base_url)
            self.finalize()

    def generate_report(self):
        table = Table(title="📊 Отчёт тестирования", box=box.ROUNDED)
        table.add_column("№", style="cyan")
        table.add_column("Тест", style="white")
        table.add_column("Статус")
        table.add_column("Детали", style="dim")
        for i, r in enumerate(self.results, 1):
            table.add_row(str(i), r["test"], f"[{r['color']}]{r['status']}[/{r['color']}]", r["details"])
        total = len(self.results)
        ok = sum(1 for r in self.results if r["success"])
        rate = (ok / total * 100) if total else 0
        stats = Panel(
            f"Всего: {total} | ✅ Успех: {ok} | ❌ Ошибки: {total - ok} | Успешность: {rate:.1f}%\n"
            f"Время: {time.time() - self.start_time:.1f} с | Скриншоты: {self.screenshot_dir}",
            title="📈 Статистика",
            border_style="cyan",
        )
        return table, stats, rate

    def create_screenshot_summary(self):
        try:
            html_file = os.path.join(self.screenshot_dir, "screenshots_summary.html")
            table, stats, rate = self.generate_report()
            with open(html_file, "w", encoding="utf-8") as f:
                f.write("""<!DOCTYPE html><html lang="ru"><head><meta charset="UTF-8"><title>Скриншоты тестов</title>
<style>body{font-family:Arial;margin:20px;background:#f5f5f5}.r{background:#fff;padding:15px;margin:10px 0;border-radius:8px}.ok{border-left:5px solid #4CAF50}.err{border-left:5px solid #f44336}.s{max-width:600px;border:1px solid #ddd;margin:8px 0;}</style></head><body><h1>📸 Скриншоты тестирования</h1><p>Успешность: """ + f"{rate:.1f}%" + """</p>""")
                for i, r in enumerate(self.results, 1):
                    cls = "ok" if r["success"] else "err"
                    f.write(f'<div class="r {cls}"><b>#{i} {r["test"]}</b> — {r["status"]}<br>{r["details"]}')
                    if r.get("screenshot"):
                        rel = os.path.relpath(r["screenshot"]["path"], self.screenshot_dir)
                        f.write(f'<br><img src="{rel}" class="s" alt="">')
                    f.write("</div>")
                f.write("</body></html>")
            self.console.print(f"[green]✓ HTML-отчёт: {html_file}[/green]")
            return html_file
        except Exception as e:
            self.console.print(f"[yellow]⚠ HTML: {e}[/yellow]")
            return None

    def write_web_report(self):
        """Пишет latest_result.json для страницы отчёта (report.php)."""
        try:
            import json
            report_dir = os.path.join(os.path.dirname(__file__), "reports")
            os.makedirs(report_dir, exist_ok=True)
            root = getattr(cfg, "PROJECT_ROOT", os.path.dirname(os.path.dirname(__file__)))
            screenshot_rel = ""
            if self.screenshot_dir and os.path.isdir(self.screenshot_dir):
                screenshot_rel = os.path.relpath(self.screenshot_dir, root).replace("\\", "/")
            total = len(self.results)
            ok_count = sum(1 for r in self.results if r["success"])
            rate = (ok_count / total * 100) if total else 0
            duration = (time.time() - self.start_time) if self.start_time else 0
            results_data = []
            for r in self.results:
                results_data.append({
                    "test": r["test"],
                    "status": r["status"],
                    "success": r["success"],
                    "details": r["details"],
                    "screenshot": r.get("screenshot", {}).get("name"),
                })
            load_graph_rel = ""
            if _report_load_graph_path and os.path.isfile(_report_load_graph_path):
                load_graph_rel = os.path.relpath(_report_load_graph_path, root).replace("\\", "/")
            network_graph_rel = ""
            if _report_network_graph_path and os.path.isfile(_report_network_graph_path):
                network_graph_rel = os.path.relpath(_report_network_graph_path, root).replace("\\", "/")
            data = {
                "timestamp": datetime.now().isoformat(),
                "screenshot_dir": screenshot_rel,
                "results": results_data,
                "total": total,
                "ok_count": ok_count,
                "rate": round(rate, 1),
                "duration_sec": round(duration, 1),
                "load_graph": load_graph_rel,
                "load_stats": _report_load_stats,
                "network_graph": network_graph_rel,
                "network_stats": _report_network_stats,
            }
            path = os.path.join(report_dir, "latest_result.json")
            with open(path, "w", encoding="utf-8") as f:
                json.dump(data, f, ensure_ascii=False, indent=2)
            # Копия по дате для выбора прогона на странице отчёта
            ts_name = datetime.now().strftime("%Y%m%d_%H%M%S")
            path_ts = os.path.join(report_dir, f"result_{ts_name}.json")
            with open(path_ts, "w", encoding="utf-8") as f:
                json.dump(data, f, ensure_ascii=False, indent=2)
            self.console.print(f"[dim]Веб-отчёт: {path}[/dim]")
        except Exception as e:
            self.console.print(f"[yellow]⚠ Веб-отчёт: {e}[/yellow]")

    def finalize(self):
        if not self.driver:
            return
        self.console.print("\n" + "=" * 60 + "\n[bold cyan]📋 ФИНАЛЬНЫЙ ОТЧЁТ[/bold cyan]\n" + "=" * 60)
        table, stats, rate = self.generate_report()
        self.console.print(table)
        self.console.print(stats)
        self.create_screenshot_summary()
        self.write_web_report()
        self.driver.quit()
        self.console.print("[dim]Драйвер закрыт[/dim]")


def main():
    console.print(Panel.fit(
        "[bold cyan]🚀 Сценарий тестирования «Танковая База»[/bold cyan]\n"
        "1. Логин и пароль  2. Стандарты кода (php -l)  3. БД на пустоты  4. Бекап  5. Нагрузка",
        border_style="cyan",
    ))
    base_url = sys.argv[1] if len(sys.argv) > 1 else cfg.BASE_URL
    console.print(f"[dim]URL: {base_url}[/dim]")
    tester = PHPSiteTester(base_url)
    tester.run_all_tests()


if __name__ == "__main__":
    main()
