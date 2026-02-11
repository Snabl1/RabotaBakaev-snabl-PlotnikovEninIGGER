import time
import random
import os
from datetime import datetime
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import Select, WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException
from rich.console import Console
from rich.table import Table
from rich.progress import Progress, SpinnerColumn, TextColumn
from rich.panel import Panel
from rich.layout import Layout
from rich import box
import sys

console = Console()

class PHPSiteTester:
    def __init__(self, base_url="http://localhost/tankbase/"):
        self.base_url = base_url.rstrip('/')
        self.driver = None
        self.results = []
        self.start_time = None
        self.console = console
        self.screenshot_dir = None
        self.test_counter = 1
        
    def setup(self):
        """Настройка драйвера и создание директории для скриншотов"""
        self.console.print("[bold cyan]🚀 Настройка тестовой среды...[/bold cyan]")
        
        # Создаем директорию для скриншотов
        timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
        self.screenshot_dir = os.path.join("screenshots", timestamp)
        os.makedirs(self.screenshot_dir, exist_ok=True)
        self.console.print(f"[dim]Скриншоты будут сохранены в: {self.screenshot_dir}[/dim]")
        
        options = webdriver.ChromeOptions()
        options.add_argument('--no-sandbox')
        options.add_argument('--disable-dev-shm-usage')
        options.add_argument('--start-maximized')
        options.add_argument('--disable-gpu')
        options.add_experimental_option('excludeSwitches', ['enable-logging'])
        
        try:
            self.driver = webdriver.Chrome(options=options)
            self.driver.implicitly_wait(5)
            self.console.print("[green]✓ Драйвер Chrome успешно запущен[/green]")
            return True
        except Exception as e:
            self.console.print(f"[red]✗ Ошибка запуска драйвера: {e}[/red]")
            return False
    
    def take_screenshot(self, test_name, description=""):
        """Создание скриншота с именованием по тесту"""
        try:
            # Создаем безопасное имя файла
            safe_test_name = "".join(c if c.isalnum() else "_" for c in test_name)
            timestamp = datetime.now().strftime("%H%M%S")
            filename = f"{self.test_counter:02d}_{safe_test_name}_{timestamp}.png"
            filepath = os.path.join(self.screenshot_dir, filename)
            
            # Делаем скриншот
            self.driver.save_screenshot(filepath)
            
            # Инкрементируем счетчик для следующего теста
            self.test_counter += 1
            
            # Возвращаем путь к файлу
            screenshot_info = {
                "path": filepath,
                "name": filename,
                "test": test_name,
                "description": description
            }
            
            self.console.print(f"[dim]📸 Скриншот сохранен: {filename}[/dim]")
            return screenshot_info
            
        except Exception as e:
            self.console.print(f"[yellow]⚠ Не удалось сохранить скриншот: {e}[/yellow]")
            return None
    
    def wait_for_element(self, selector, by=By.CSS_SELECTOR, timeout=10):
        """Ожидание элемента"""
        try:
            element = WebDriverWait(self.driver, timeout).until(
                EC.presence_of_element_located((by, selector))
            )
            return element
        except TimeoutException:
            return None
    
    def safe_click(self, selector, by=By.CSS_SELECTOR):
        """Безопасный клик"""
        try:
            element = self.wait_for_element(selector, by)
            if element:
                element.click()
                return True
            return False
        except Exception as e:
            self.console.print(f"[yellow]⚠ Ошибка клика: {e}[/yellow]")
            return False
    
    def safe_send_keys(self, selector, text, by=By.CSS_SELECTOR):
        """Безопасная отправка текста"""
        try:
            element = self.wait_for_element(selector, by)
            if element:
                element.clear()
                element.send_keys(text)
                return True
            return False
        except Exception as e:
            self.console.print(f"[yellow]⚠ Ошибка отправки текста: {e}[/yellow]")
            return False
    
    def record_result(self, test_name, success, details="", screenshot_info=None):
        """Запись результата теста"""
        status = "✅ УСПЕХ" if success else "❌ ОШИБКА"
        color = "green" if success else "red"
        
        # Добавляем информацию о скриншоте
        if screenshot_info:
            details_with_screenshot = f"{details} [dim](скриншот: {screenshot_info['name']})[/dim]"
        else:
            details_with_screenshot = f"{details} [dim](скриншот не сделан)[/dim]"
        
        self.results.append({
            "test": test_name,
            "status": status,
            "details": details_with_screenshot,
            "color": color,
            "screenshot": screenshot_info,
            "success": success
        })
        
        # Вывод в реальном времени
        if success:
            self.console.print(f"[{color}]✓ {test_name}: {details}[/{color}]")
            if screenshot_info:
                self.console.print(f"[dim]   📸 Скриншот: {screenshot_info['name']}[/dim]")
        else:
            self.console.print(f"[{color}]✗ {test_name}: {details}[/{color}]")
            if screenshot_info:
                self.console.print(f"[dim]   📸 Скриншот ошибки: {screenshot_info['name']}[/dim]")
    
    def test_navigation(self):
        """Тест навигации по меню с скриншотами для каждой страницы"""
        self.console.print("\n[bold cyan]🧭 Тестирование навигации...[/bold cyan]")
        
        menu_items = [
            ("Клиенты", "clients.php"),
            ("Категории", "categories.php"),
            ("Комплектующие", "components.php"),
            ("Заказы", "orders.php"),
            ("Зоны доставки", "delivery_zones.php"),
            ("Гарантии", "warranties.php")
        ]
        
        for name, url in menu_items:
            try:
                self.driver.get(f"{self.base_url}/{url}")
                time.sleep(2)  # Даем время для полной загрузки
                
                # Проверка заголовка страницы
                page_title = self.driver.title
                
                # Создаем скриншот каждой страницы
                screenshot_info = self.take_screenshot(
                    f"Навигация_{name}", 
                    f"Страница: {page_title}"
                )
                
                if name.lower() in page_title.lower():
                    self.record_result(
                        f"Навигация: {name}", 
                        True, 
                        f"Загружено: {page_title}",
                        screenshot_info
                    )
                else:
                    # Проверяем наличие контента на странице
                    body_text = self.driver.find_element(By.TAG_NAME, "body").text
                    if any(keyword in body_text for keyword in [name, name[:4]]):
                        self.record_result(
                            f"Навигация: {name}", 
                            True, 
                            "Контент найден",
                            screenshot_info
                        )
                    else:
                        self.record_result(
                            f"Навигация: {name}", 
                            False, 
                            "Контент не найден",
                            screenshot_info
                        )
                        
            except Exception as e:
                # Делаем скриншот при ошибке
                screenshot_info = self.take_screenshot(
                    f"Навигация_{name}_ERROR", 
                    f"Ошибка: {str(e)}"
                )
                self.record_result(
                    f"Навигация: {name}", 
                    False, 
                    str(e),
                    screenshot_info
                )
    
    def test_categories(self):
        """Тест управления категориями с скриншотами каждого шага"""
        self.console.print("\n[bold cyan]📂 Тестирование категорий...[/bold cyan]")
        
        # Переход на страницу категорий
        self.driver.get(f"{self.base_url}/categories.php")
        time.sleep(2)
        
        # Скриншот начальной страницы категорий
        initial_screenshot = self.take_screenshot(
            "Категории_начало", 
            "Страница категорий до теста"
        )
        
        # Тест добавления категории
        try:
            # Ввод названия категории
            category_name = f"ТестКатегория_{random.randint(1000, 9999)}"
            input_field = self.wait_for_element("input[name='category_name']")
            
            if input_field:
                input_field.send_keys(category_name)
                
                # Скриншот перед добавлением
                before_add_screenshot = self.take_screenshot(
                    "Категории_перед_добавлением", 
                    f"Форма с данными: {category_name}"
                )
                
                # Нажатие кнопки добавления
                add_button = self.driver.find_element(By.CSS_SELECTOR, "button[name='add']")
                add_button.click()
                time.sleep(2)
                
                # Скриншот после добавления
                after_add_screenshot = self.take_screenshot(
                    "Категории_после_добавления", 
                    "После отправки формы"
                )
                
                # Проверка успешного добавления
                page_source = self.driver.page_source
                if "успешно" in page_source.lower() or category_name in page_source:
                    self.record_result(
                        "Добавление категории", 
                        True, 
                        f"Категория '{category_name}' добавлена",
                        after_add_screenshot
                    )
                else:
                    self.record_result(
                        "Добавление категории", 
                        False, 
                        "Сообщение об успехе не найдено",
                        after_add_screenshot
                    )
            else:
                self.record_result(
                    "Добавление категории", 
                    False, 
                    "Поле ввода не найдено",
                    initial_screenshot
                )
                
        except Exception as e:
            error_screenshot = self.take_screenshot(
                "Категории_ошибка_добавления", 
                f"Ошибка: {str(e)}"
            )
            self.record_result(
                "Добавление категории", 
                False, 
                str(e),
                error_screenshot
            )
        
        # Тест поиска категорий
        try:
            # Возвращаемся на страницу категорий
            self.driver.get(f"{self.base_url}/categories.php")
            time.sleep(2)
            
            search_field = self.wait_for_element("#categorySearch")
            if search_field:
                search_field.send_keys("Тест")
                time.sleep(1)
                
                # Скриншот после поиска
                search_screenshot = self.take_screenshot(
                    "Категории_поиск", 
                    "Результаты поиска по 'Тест'"
                )
                
                self.record_result(
                    "Поиск категорий", 
                    True, 
                    "Поиск выполнен",
                    search_screenshot
                )
            else:
                self.record_result(
                    "Поиск категорий", 
                    False, 
                    "Поле поиска не найдено",
                    initial_screenshot
                )
        except Exception as e:
            error_screenshot = self.take_screenshot(
                "Категории_ошибка_поиска", 
                f"Ошибка поиска: {str(e)}"
            )
            self.record_result(
                "Поиск категорий", 
                False, 
                str(e),
                error_screenshot
            )
    
    def test_clients(self):
        """Тест управления клиентами с скриншотами"""
        self.console.print("\n[bold cyan]👥 Тестирование клиентов...[/bold cyan]")
        
        self.driver.get(f"{self.base_url}/clients.php")
        time.sleep(2)
        
        # Скриншот начальной страницы
        initial_screenshot = self.take_screenshot(
            "Клиенты_начало", 
            "Страница клиентов до теста"
        )
        
        # Тест добавления клиента
        try:
            client_data = {
                "last_name": f"Иванов_{random.randint(100, 999)}",
                "first_name": "Иван",
                "middle_name": "Иванович",
                "address": f"ул. Тестовая, д. {random.randint(1, 100)}",
                "phone": f"+7{random.randint(9000000000, 9999999999)}"
            }
            
            # Заполнение формы
            fields = ["last_name", "first_name", "middle_name", "address", "phone"]
            for field in fields:
                selector = f"input[name='{field}']"
                if not self.safe_send_keys(selector, client_data[field]):
                    self.record_result(
                        "Добавление клиента", 
                        False, 
                        f"Не удалось заполнить поле {field}",
                        initial_screenshot
                    )
                    return
            
            # Скриншот заполненной формы
            filled_form_screenshot = self.take_screenshot(
                "Клиенты_заполненная_форма", 
                f"Форма с данными клиента {client_data['last_name']}"
            )
            
            # Отправка формы
            add_button = self.wait_for_element("button[name='add']")
            if add_button:
                add_button.click()
                time.sleep(2)
                
                # Скриншот результата
                result_screenshot = self.take_screenshot(
                    "Клиенты_результат_добавления", 
                    "После добавления клиента"
                )
                
                # Проверка результата
                page_source = self.driver.page_source
                if "успешно" in page_source.lower():
                    self.record_result(
                        "Добавление клиента", 
                        True, 
                        f"Клиент {client_data['last_name']} добавлен",
                        result_screenshot
                    )
                else:
                    self.record_result(
                        "Добавление клиента", 
                        False, 
                        "Сообщение об успехе не найдено",
                        result_screenshot
                    )
                    
        except Exception as e:
            error_screenshot = self.take_screenshot(
                "Клиенты_ошибка_добавления", 
                f"Ошибка: {str(e)}"
            )
            self.record_result(
                "Добавление клиента", 
                False, 
                str(e),
                error_screenshot
            )
    
    def test_components(self):
        """Тест управления комплектующими с скриншотами"""
        self.console.print("\n[bold cyan]⚙️ Тестирование комплектующих...[/bold cyan]")
        
        self.driver.get(f"{self.base_url}/components.php")
        time.sleep(2)
        
        # Скриншот страницы
        page_screenshot = self.take_screenshot(
            "Комплектующие_страница", 
            "Страница комплектующих"
        )
        
        # Проверка наличия таблицы
        try:
            table = self.wait_for_element("table")
            if table:
                rows = table.find_elements(By.TAG_NAME, "tr")
                if len(rows) > 1:  # Больше 1, включая заголовок
                    # Скриншот таблицы
                    table_screenshot = self.take_screenshot(
                        "Комплектующие_таблица", 
                        f"Таблица с {len(rows)-1} строками"
                    )
                    self.record_result(
                        "Таблица комплектующих", 
                        True, 
                        f"Найдено {len(rows)-1} строк",
                        table_screenshot
                    )
                else:
                    self.record_result(
                        "Таблица комплектующих", 
                        True, 
                        "Таблица отображается (возможно пустая)",
                        page_screenshot
                    )
            else:
                self.record_result(
                    "Таблица комплектующих", 
                    False, 
                    "Таблица не найдена",
                    page_screenshot
                )
                
        except Exception as e:
            error_screenshot = self.take_screenshot(
                "Комплектующие_ошибка", 
                f"Ошибка: {str(e)}"
            )
            self.record_result(
                "Таблица комплектующих", 
                False, 
                str(e),
                error_screenshot
            )
    
    def test_orders(self):
        """Тест заказов с скриншотами"""
        self.console.print("\n[bold cyan]📦 Тестирование заказов...[/bold cyan]")
        
        self.driver.get(f"{self.base_url}/orders.php")
        time.sleep(3)
        
        # Скриншот всей страницы заказов
        page_screenshot = self.take_screenshot(
            "Заказы_страница", 
            "Страница создания заказа"
        )
        
        # Проверка основных элементов формы заказа
        elements_to_check = [
            ("select[name='client_id']", "Выбор клиента", By.CSS_SELECTOR),
            ("input[name='assembly_needed']", "Чекбокс сборки", By.CSS_SELECTOR),
            ("#deliveryCheck", "Чекбокс доставки", By.CSS_SELECTOR),
            ("#warrantySelect", "Выбор гарантии", By.CSS_SELECTOR),
            ("#grandTotal", "Итоговая стоимость", By.CSS_SELECTOR)
        ]
        
        all_found = True
        found_elements = []
        missing_elements = []
        
        for selector, name, by in elements_to_check:
            try:
                element = self.wait_for_element(selector, by, timeout=3)
                if element:
                    found_elements.append(name)
                    
                    # Скриншот отдельного элемента (если нужно)
                    if selector == "#grandTotal":
                        element_screenshot = self.take_screenshot(
                            "Заказы_итоговая_стоимость", 
                            f"Элемент: {name}"
                        )
                else:
                    missing_elements.append(name)
                    all_found = False
                    
            except Exception as e:
                missing_elements.append(f"{name} (ошибка: {str(e)})")
                all_found = False
        
        if all_found:
            self.record_result(
                "Элементы формы заказа", 
                True, 
                f"Все элементы найдены: {', '.join(found_elements)}",
                page_screenshot
            )
        else:
            # Делаем скриншот для отладки
            debug_screenshot = self.take_screenshot(
                "Заказы_отсутствующие_элементы", 
                f"Найдено: {', '.join(found_elements) if found_elements else 'нет'}. "
                f"Отсутствуют: {', '.join(missing_elements)}"
            )
            self.record_result(
                "Элементы формы заказа", 
                False, 
                f"Отсутствуют элементы: {', '.join(missing_elements)}",
                debug_screenshot
            )
    
    def test_delivery_zones(self):
        """Тест зон доставки с скриншотами"""
        self.console.print("\n[bold cyan]🗺️ Тестирование зон доставки...[/bold cyan]")
        
        self.driver.get(f"{self.base_url}/delivery_zones.php")
        time.sleep(2)
        
        # Скриншот страницы
        page_screenshot = self.take_screenshot(
            "Зоны_доставки_страница", 
            "Страница зон доставки"
        )
        
        # Проверка формы
        try:
            form_elements = [
                "input[name='zone_name']",
                "input[name='base_price']",
                "input[name='price_per_km']",
                "input[name='min_distance']"
            ]
            
            found_count = 0
            for element_selector in form_elements:
                if self.wait_for_element(element_selector, timeout=3):
                    found_count += 1
            
            # Скриншот формы
            form_screenshot = self.take_screenshot(
                "Зоны_доставки_форма", 
                f"Найдено {found_count} из {len(form_elements)} элементов формы"
            )
            
            if found_count == len(form_elements):
                self.record_result(
                    "Форма зон доставки", 
                    True, 
                    f"Все {found_count} элементов формы найдены",
                    form_screenshot
                )
            else:
                self.record_result(
                    "Форма зон доставки", 
                    False, 
                    f"Найдено только {found_count} из {len(form_elements)} элементов",
                    form_screenshot
                )
                    
        except Exception as e:
            error_screenshot = self.take_screenshot(
                "Зоны_доставки_ошибка", 
                f"Ошибка: {str(e)}"
            )
            self.record_result(
                "Форма зон доставки", 
                False, 
                str(e),
                error_screenshot
            )
    
    def test_warranties(self):
        """Тест гарантий с скриншотами"""
        self.console.print("\n[bold cyan]🛡️ Тестирование гарантий...[/bold cyan]")
        
        self.driver.get(f"{self.base_url}/warranties.php")
        time.sleep(2)
        
        # Скриншот страницы
        page_screenshot = self.take_screenshot(
            "Гарантии_страница", 
            "Страница гарантий"
        )
        
        # Проверка элементов формы
        try:
            warranty_select = self.wait_for_element("select[name='warranty_type']", timeout=3)
            if warranty_select:
                select = Select(warranty_select)
                options = select.options
                
                # Скриншот выбора гарантии
                select_screenshot = self.take_screenshot(
                    "Гарантии_выбор_типа", 
                    f"Доступно {len(options)} вариантов гарантии"
                )
                
                self.record_result(
                    "Выбор типа гарантии", 
                    True, 
                    f"Доступно {len(options)} вариантов",
                    select_screenshot
                )
            else:
                self.record_result(
                    "Выбор типа гарантии", 
                    False, 
                    "Селект не найден",
                    page_screenshot
                )
                
        except Exception as e:
            error_screenshot = self.take_screenshot(
                "Гарантии_ошибка", 
                f"Ошибка: {str(e)}"
            )
            self.record_result(
                "Форма гарантий", 
                False, 
                str(e),
                error_screenshot
            )
    
    def test_spinner_game(self):
        """Тест игрового спиннера с скриншотом"""
        self.console.print("\n[bold cyan]🎮 Тестирование игрового спиннера...[/bold cyan]")
        
        self.driver.get(f"{self.base_url}/index.php")
        time.sleep(2)
        
        # Скриншот главной страницы
        main_screenshot = self.take_screenshot(
            "Спиннер_главная", 
            "Главная страница с игровым спиннером"
        )
        
        # Проверка спиннера
        try:
            spinner = self.wait_for_element("#mainSpinner", timeout=5)
            if spinner:
                # Дополнительный скриншот спиннера крупным планом
                self.driver.execute_script("arguments[0].scrollIntoView();", spinner)
                time.sleep(1)
                spinner_closeup = self.take_screenshot(
                    "Спиннер_крупно", 
                    "Игровой спиннер крупным планом"
                )
                
                self.record_result(
                    "Игровой спиннер", 
                    True, 
                    "Спиннер найден на главной странице",
                    spinner_closeup
                )
            else:
                self.record_result(
                    "Игровой спиннер", 
                    False, 
                    "Спиннер не найден",
                    main_screenshot
                )
                
        except Exception as e:
            error_screenshot = self.take_screenshot(
                "Спиннер_ошибка", 
                f"Ошибка: {str(e)}"
            )
            self.record_result(
                "Игровой спиннер", 
                False, 
                str(e),
                error_screenshot
            )
    
    def test_flash_messages(self):
        """Тест флеш-сообщений с скриншотами"""
        self.console.print("\n[bold cyan]💬 Тестирование уведомлений...[/bold cyan]")
        
        pages_to_check = ["categories.php", "clients.php", "components.php"]
        
        for page in pages_to_check:
            try:
                self.driver.get(f"{self.base_url}/{page}")
                time.sleep(2)
                
                # Скриншот страницы
                page_screenshot = self.take_screenshot(
                    f"Уведомления_{page.replace('.php', '')}", 
                    f"Страница: {page}"
                )
                
                # Ищем элементы alert
                alerts = self.driver.find_elements(By.CSS_SELECTOR, ".alert, .flash-message, [class*='alert']")
                if alerts:
                    self.record_result(
                        f"Уведомления на {page}", 
                        True, 
                        f"Найдено {len(alerts)} уведомлений",
                        page_screenshot
                    )
                else:
                    # Проверяем наличие блока с сообщениями
                    body_text = self.driver.find_element(By.TAG_NAME, "body").text
                    if any(word in body_text.lower() for word in ["успешно", "ошибка", "внимание", "предупреждение"]):
                        self.record_result(
                            f"Уведомления на {page}", 
                            True, 
                            "Текстовые сообщения найдены",
                            page_screenshot
                        )
                    else:
                        self.record_result(
                            f"Уведомления на {page}", 
                            True, 
                            "Активных уведомлений нет",
                            page_screenshot
                        )
                        
            except Exception as e:
                error_screenshot = self.take_screenshot(
                    f"Уведомления_{page}_ошибка", 
                    f"Ошибка: {str(e)}"
                )
                self.record_result(
                    f"Уведомления на {page}", 
                    False, 
                    str(e),
                    error_screenshot
                )
    
    def test_responsive_elements(self):
        """Тест адаптивных элементов с скриншотами"""
        self.console.print("\n[bold cyan]📱 Тестирование адаптивных элементов...[/bold cyan]")
        
        # Используем страницу категорий для теста
        self.driver.get(f"{self.base_url}/categories.php")
        time.sleep(2)
        
        # Скриншот всей страницы
        full_page_screenshot = self.take_screenshot(
            "Адаптивные_элементы_полная_страница", 
            "Полная страница для проверки адаптивности"
        )
        
        elements_to_test = [
            ("Статистические карточки", ".stat-card"),
            ("Прогресс-бары", ".progress-bar"),
            ("Таблицы", "table"),
            ("Кнопки", ".btn"),
            ("Панели навигации", ".menu-nav"),
            ("Поиск", "input[type='text']")
        ]
        
        found_elements = []
        missing_elements = []
        
        for name, selector in elements_to_test:
            try:
                elements = self.driver.find_elements(By.CSS_SELECTOR, selector)
                if elements:
                    found_elements.append(f"{name} ({len(elements)} шт.)")
                    
                    # Скриншот для интересных элементов
                    if name in ["Статистические карточки", "Таблицы"]:
                        element_screenshot = self.take_screenshot(
                            f"Адаптивные_{name.replace(' ', '_')}", 
                            f"{name}: {len(elements)} элементов"
                        )
                else:
                    missing_elements.append(name)
                    
            except Exception as e:
                missing_elements.append(f"{name} (ошибка: {str(e)})")
        
        # Делаем итоговый скриншот
        summary_screenshot = self.take_screenshot(
            "Адаптивные_элементы_итог", 
            f"Найдено: {len(found_elements)} типов элементов"
        )
        
        if missing_elements:
            self.record_result(
                "Адаптивные элементы", 
                False, 
                f"Найдено: {', '.join(found_elements)}. "
                f"Отсутствуют: {', '.join(missing_elements)}",
                summary_screenshot
            )
        else:
            self.record_result(
                "Адаптивные элементы", 
                True, 
                f"Все элементы найдены: {', '.join(found_elements)}",
                summary_screenshot
            )
    
    def run_all_tests(self):
        """Запуск всех тестов с созданием скриншотов"""
        self.start_time = time.time()
        
        if not self.setup():
            return
        
        try:
            # Запуск тестов с прогресс-баром
            tests = [
                ("Навигация", self.test_navigation),
                ("Категории", self.test_categories),
                ("Клиенты", self.test_clients),
                ("Комплектующие", self.test_components),
                ("Заказы", self.test_orders),
                ("Зоны доставки", self.test_delivery_zones),
                ("Гарантии", self.test_warranties),
                ("Игровой спиннер", self.test_spinner_game),
                ("Уведомления", self.test_flash_messages),
                ("Адаптивные элементы", self.test_responsive_elements)
            ]
            
            with Progress(
                SpinnerColumn(),
                TextColumn("[progress.description]{task.description}"),
                console=self.console
            ) as progress:
                task = progress.add_task("[cyan]Выполнение тестов...", total=len(tests))
                
                for test_name, test_func in tests:
                    progress.update(task, description=f"[cyan]Тест: {test_name} 📸")
                    test_func()
                    progress.advance(task)
        
        except Exception as e:
            self.console.print(f"[red]⚠ Критическая ошибка: {e}[/red]")
        finally:
            self.finalize()
    
    def generate_report(self):
        """Генерация отчета со ссылками на скриншоты"""
        # Создаем таблицу с результатами
        table = Table(title="📊 Отчет о тестировании PHP сайта", box=box.ROUNDED)
        
        table.add_column("№", style="cyan", no_wrap=True)
        table.add_column("Тест", style="white")
        table.add_column("Статус", justify="center")
        table.add_column("Детали", style="dim")
        
        success_count = 0
        error_count = 0
        screenshots_count = 0
        
        for i, result in enumerate(self.results, 1):
            # Подсчитываем скриншоты
            if result.get("screenshot"):
                screenshots_count += 1
            
            table.add_row(
                str(i),
                result["test"],
                f"[{result['color']}]{result['status']}[/{result['color']}]",
                result["details"]
            )
            
            if result["success"]:
                success_count += 1
            else:
                error_count += 1
        
        # Панель статистики
        total_tests = len(self.results)
        success_rate = (success_count / total_tests * 100) if total_tests > 0 else 0
        
        stats_panel = Panel(
            f"[bold]Всего тестов:[/bold] {total_tests}\n"
            f"[green]✅ Успешных:[/green] {success_count}\n"
            f"[red]❌ Ошибок:[/red] {error_count}\n"
            f"[yellow]📈 Успешность:[/yellow] {success_rate:.1f}%\n"
            f"[cyan]📸 Скриншотов:[/cyan] {screenshots_count}\n"
            f"[cyan]⏱ Время выполнения:[/cyan] {time.time() - self.start_time:.1f} сек\n"
            f"[dim]Папка со скриншотами: {self.screenshot_dir}[/dim]",
            title="📈 Статистика тестирования",
            border_style="cyan"
        )
        
        return table, stats_panel, success_rate, screenshots_count
    
    def create_screenshot_summary(self):
        """Создание HTML файла со всеми скриншотами"""
        try:
            html_file = os.path.join(self.screenshot_dir, "screenshots_summary.html")
            
            with open(html_file, 'w', encoding='utf-8') as f:
                f.write("""
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📸 Скриншоты тестирования PHP сайта</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: #f5f5f5;
        }
        .header {
            background: #333;
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .test-result {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .success {
            border-left: 5px solid #4CAF50;
        }
        .error {
            border-left: 5px solid #F44336;
        }
        .screenshot {
            max-width: 600px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin: 10px 0;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .screenshot:hover {
            transform: scale(1.02);
        }
        .test-name {
            font-weight: bold;
            font-size: 1.2em;
            margin-bottom: 5px;
        }
        .test-details {
            color: #666;
            margin-bottom: 10px;
        }
        .screenshot-container {
            margin-top: 10px;
        }
        .timestamp {
            color: #888;
            font-size: 0.9em;
            margin-top: 5px;
        }
        .stats {
            background: #e8f5e8;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .folder-info {
            background: #e3f2fd;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📸 Скриншоты тестирования PHP сайта</h1>
        <p>Отчет о автоматическом тестировании с Selenium</p>
    </div>
""")
                
                # Добавляем статистику
                table, stats_panel, success_rate, screenshots_count = self.generate_report()
                
                f.write(f"""
    <div class="stats">
        <h3>📊 Статистика тестирования</h3>
        <p><strong>Всего тестов:</strong> {len(self.results)}</p>
        <p><strong>Успешных:</strong> {sum(1 for r in self.results if r['success'])}</p>
        <p><strong>Ошибок:</strong> {sum(1 for r in self.results if not r['success'])}</p>
        <p><strong>Успешность:</strong> {success_rate:.1f}%</p>
        <p><strong>Скриншотов:</strong> {screenshots_count}</p>
        <p><strong>Папка со скриншотами:</strong> {os.path.basename(self.screenshot_dir)}</p>
    </div>
""")
                
                # Добавляем результаты тестов со скриншотами
                for i, result in enumerate(self.results, 1):
                    status_class = "success" if result["success"] else "error"
                    
                    f.write(f"""
    <div class="test-result {status_class}">
        <div class="test-name">Тест #{i}: {result['test']}</div>
        <div class="test-details">
            Статус: <strong>{result['status']}</strong><br>
            {result['details'].replace('[dim]', '<span style="color: #888;">').replace('[/dim]', '</span>')}
        </div>
""")
                    
                    if result.get("screenshot"):
                        screenshot = result["screenshot"]
                        # Получаем относительный путь для HTML
                        rel_path = os.path.relpath(screenshot["path"], self.screenshot_dir)
                        
                        f.write(f"""
        <div class="screenshot-container">
            <div><strong>Скриншот:</strong> {screenshot['name']}</div>
            <div class="timestamp">Описание: {screenshot.get('description', 'Нет описания')}</div>
            <img src="{rel_path}" alt="{result['test']}" class="screenshot"
                 onclick="window.open(this.src, '_blank')">
        </div>
""")
                    
                    f.write("    </div>")
                
                f.write("""
    <script>
        // Автоматически открывать скриншоты в новой вкладке при клике
        document.addEventListener('DOMContentLoaded', function() {
            const screenshots = document.querySelectorAll('.screenshot');
            screenshots.forEach(img => {
                img.addEventListener('click', function() {
                    window.open(this.src, '_blank');
                });
            });
        });
    </script>
</body>
</html>
""")
            
            self.console.print(f"\n[green]✓ HTML отчет создан: {html_file}[/green]")
            self.console.print(f"[dim]Откройте файл в браузере для просмотра всех скриншотов[/dim]")
            
            return html_file
            
        except Exception as e:
            self.console.print(f"[yellow]⚠ Не удалось создать HTML отчет: {e}[/yellow]")
            return None
    
    def finalize(self):
        """Завершение тестирования с созданием HTML отчета"""
        if self.driver:
            # Вывод отчета
            self.console.print("\n" + "="*60)
            self.console.print("[bold cyan]📋 ФИНАЛЬНЫЙ ОТЧЕТ[/bold cyan]")
            self.console.print("="*60)
            
            table, stats_panel, success_rate, screenshots_count = self.generate_report()
            
            # Создаем layout для красивого отображения
            layout = Layout()
            layout.split_column(
                Layout(name="upper", size=15),
                Layout(name="lower")
            )
            
            layout["upper"].update(table)
            layout["lower"].update(stats_panel)
            
            self.console.print(layout)
            
            # Создаем HTML отчет со скриншотами
            html_report = self.create_screenshot_summary()
            
            # Рекомендации
            if success_rate < 70:
                self.console.print("\n[bold yellow]⚠ ТРЕБУЕТСЯ ВНИМАНИЕ:[/bold yellow]")
                self.console.print("• Проверьте доступность сайта")
                self.console.print("• Убедитесь что PHP сервер запущен")
                self.console.print("• Проверьте подключение к базам данных")
                self.console.print("• Убедитесь что все файлы на месте")
                self.console.print(f"• Посмотрите скриншоты ошибок в папке: {self.screenshot_dir}")
            elif success_rate < 90:
                self.console.print("\n[bold green]✓ ХОРОШИЙ РЕЗУЛЬТАТ:[/bold green]")
                self.console.print("• Сайт работает корректно")
                self.console.print("• Некоторые второстепенные функции требуют проверки")
                self.console.print(f"• Все скриншоты сохранены в: {self.screenshot_dir}")
                if html_report:
                    self.console.print(f"• HTML отчет: file://{os.path.abspath(html_report)}")
            else:
                self.console.print("\n[bold green]🎉 ОТЛИЧНЫЙ РЕЗУЛЬТАТ![/bold green]")
                self.console.print("• Сайт работает стабильно")
                self.console.print("• Все основные функции доступны")
                self.console.print(f"• Создано {screenshots_count} скриншотов")
                if html_report:
                    self.console.print(f"• HTML отчет с скриншотами: file://{os.path.abspath(html_report)}")
            
            # Закрываем драйвер
            self.driver.quit()
            self.console.print("\n[dim]Драйвер закрыт[/dim]")

def main():
    """Основная функция"""
    console.print(Panel.fit(
        "[bold cyan]🚀 ТЕСТЕР PHP САЙТА С SELENIUM И RICH[/bold cyan]\n"
        "[dim]Автоматическое тестирование с созданием скриншотов[/dim]",
        border_style="cyan"
    ))
    
    # Получаем URL от пользователя или используем дефолтный
    base_url = "http://localhost:3000"
    
    if len(sys.argv) > 1:
        base_url = sys.argv[1]
    else:
        console.print(f"[dim]Используется URL по умолчанию: {base_url}[/dim]")
        console.print("[dim]Для указания другого URL: python tester.py http://ваш-сайт[/dim]\n")
    
    tester = PHPSiteTester(base_url)
    
    # Запускаем тесты
    console.print("[bold]Начало тестирования со скриншотами...[/bold]")
    console.print(f"[dim]Базовая ссылка: {base_url}[/dim]")
    console.print("[dim]Будет создана папка screenshots/ с датой и временем[/dim]")
    console.print("[dim]Тестирование может занять несколько минут...[/dim]\n")
    
    tester.run_all_tests()

if __name__ == "__main__":
    main()