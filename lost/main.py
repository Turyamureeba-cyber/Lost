from kivy.config import Config
Config.set('graphics', 'width', '360')
Config.set('graphics', 'height', '780')

import sqlite3
import threading
import os
import datetime
from PIL import Image as PILImage
from pyzbar.pyzbar import decode as zbar_decode

from kivy.core.window import Window
from kivy.lang import Builder
from kivy.properties import ListProperty, ObjectProperty
from kivy.uix.boxlayout import BoxLayout
from kivy.uix.image import Image
from kivy.clock import mainthread

from kivymd.app import MDApp
from kivymd.uix.screen import MDScreen
from kivymd.uix.toolbar import MDTopAppBar
from kivymd.uix.button import MDRaisedButton, MDIconButton
from kivymd.uix.textfield import MDTextField
from kivymd.uix.card import MDCard
from kivymd.uix.dialog import MDDialog
from kivymd.uix.list import OneLineListItem, MDList
from kivymd.uix.snackbar import Snackbar

# Plyer for mobile features
from plyer import filechooser, camera

KV = '''
<MainScreen>:
    name: 'main'
    BoxLayout:
        orientation: 'vertical'
        canvas.before:
            Color:
                rgba: app.gradient_top
            Rectangle:
                pos: self.pos
                size: self.width, dp(220)
        
        MDTopAppBar:
            id: toolbar
            title: "FindIt - Lost & Found"
            elevation: 10
            left_action_items: [['menu', lambda x: app.open_menu()]]
            right_action_items: [['magnify', lambda x: app.open_search()]]
        
        BoxLayout:
            orientation: 'vertical'
            padding: dp(12)
            spacing: dp(12)
            
            MDCard:
                orientation: 'vertical'
                padding: dp(12)
                size_hint_y: None
                height: dp(140)
                md_bg_color: 1,1,1,0.08
                radius: [12,]
                BoxLayout:
                    orientation: 'vertical'
                    spacing: dp(8)
                    MDLabel:
                        text: 'Smart. Secure. Local.'
                        font_style: 'H5'
                        size_hint_y: None
                        height: self.texture_size[1]
                    MDLabel:
                        text: 'Scan existing barcodes on IDs and cards. Owners unlock finder contact with a small fee.'
                        theme_text_color: 'Secondary'
                        size_hint_y: None
                        height: self.texture_size[1]
            
            BoxLayout:
                size_hint_y: None
                height: dp(64)
                spacing: dp(10)
                MDRaisedButton:
                    text: 'Report Found Item'
                    on_release: app.open_report_found()
                    md_bg_color: app.accent
                MDRaisedButton:
                    text: 'Search as Owner'
                    on_release: app.open_search()
                    md_bg_color: app.primary
            
            MDCard:
                padding: dp(8)
                radius: [12]
                md_bg_color: 1,1,1,0.04
                MDLabel:
                    text: 'Recent entries'
                    font_style: 'H6'
                    size_hint_y: None
                    height: self.texture_size[1]
                ScrollView:
                    size_hint_y: None
                    height: dp(260)
                    MDList:
                        id: recent_list

<ReportFoundScreen>:
    name: 'report'
    BoxLayout:
        orientation: 'vertical'
        MDTopAppBar:
            title: 'Report Found Item'
            left_action_items: [['arrow-left', lambda x: app.go_home()]]
        
        ScrollView:
            BoxLayout:
                orientation: 'vertical'
                padding: dp(12)
                spacing: dp(8)
                size_hint_y: None
                height: self.minimum_height
                
                MDTextField:
                    id: code_field
                    hint_text: 'Manual code (optional)'
                
                MDTextField:
                    id: type_field
                    hint_text: 'Item type (e.g., National ID)'
                
                MDTextField:
                    id: location_field
                    hint_text: 'Found location'
                
                BoxLayout:
                    size_hint_y: None
                    height: dp(48)
                    spacing: dp(8)
                    MDRaisedButton:
                        text: 'Scan with Camera'
                        on_release: app.scan_with_camera()
                    MDRaisedButton:
                        text: 'Select Image'
                        on_release: app.pick_image()
                
                MDRaisedButton:
                    text: 'Submit'
                    on_release: app.submit_found_item(code_field.text, type_field.text, location_field.text)

<SearchScreen>:
    name: 'search'
    BoxLayout:
        orientation: 'vertical'
        MDTopAppBar:
            title: 'Search Lost Items'
            left_action_items: [['arrow-left', lambda x: app.go_home()]]
        
        BoxLayout:
            orientation: 'vertical'
            padding: dp(12)
            spacing: dp(8)
            
            MDTextField:
                id: search_field
                hint_text: 'Enter partial code'
            
            MDTextField:
                id: search_type
                hint_text: 'Item type (optional)'
            
            MDRaisedButton:
                text: 'Search'
                on_release: app.perform_search(search_field.text, search_type.text)
            
            ScrollView:
                MDList:
                    id: results_list
                    size_hint_y: None
                    height: self.minimum_height
'''

class MainScreen(MDScreen):
    pass

class ReportFoundScreen(MDScreen):
    pass

class SearchScreen(MDScreen):
    pass

class DB:
    def __init__(self, path='found_items.db'):
        self.path = path
        self.conn = sqlite3.connect(self.path, check_same_thread=False)
        self._create()

    def _create(self):
        c = self.conn.cursor()
        c.execute('''
            CREATE TABLE IF NOT EXISTS items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                item_code TEXT,
                item_type TEXT,
                location TEXT,
                found_date TEXT,
                finder_contact TEXT,
                image_path TEXT,
                unlocked INTEGER DEFAULT 0
            )
        ''')
        self.conn.commit()

    def add_item(self, item_code, item_type, location, finder_contact, image_path):
        c = self.conn.cursor()
        c.execute('''INSERT INTO items (item_code, item_type, location, found_date, finder_contact, image_path)
                     VALUES (?, ?, ?, ?, ?, ?)''',
                  (item_code, item_type, location, datetime.datetime.utcnow().isoformat(), finder_contact, image_path))
        self.conn.commit()
        return c.lastrowid

    def recent_items(self, limit=10):
        c = self.conn.cursor()
        c.execute('SELECT id, item_code, item_type, location, found_date, image_path FROM items ORDER BY id DESC LIMIT ?', (limit,))
        return c.fetchall()

    def search(self, code_like='', item_type=''):
        c = self.conn.cursor()
        query = 'SELECT id, item_code, item_type, location, found_date, unlocked, image_path FROM items WHERE 1=1'
        params = []
        if code_like:
            query += ' AND item_code LIKE ?'
            params.append('%' + code_like + '%')
        if item_type:
            query += ' AND item_type LIKE ?'
            params.append('%' + item_type + '%')
        query += ' ORDER BY id DESC'
        c.execute(query, params)
        return c.fetchall()

    def get_item(self, item_id):
        c = self.conn.cursor()
        c.execute('SELECT id, item_code, item_type, location, found_date, finder_contact, unlocked, image_path FROM items WHERE id=?', (item_id,))
        return c.fetchone()

    def unlock_item(self, item_id):
        c = self.conn.cursor()
        c.execute('UPDATE items SET unlocked=1 WHERE id=?', (item_id,))
        self.conn.commit()

class FindItApp(MDApp):
    gradient_top = ListProperty([0.0, 0.5, 0.8, 1])
    primary = ListProperty([0.0, 0.45, 0.7, 1])
    accent = ListProperty([0.9, 0.45, 0.2, 1])
    
    def __init__(self, **kwargs):
        super().__init__(**kwargs)
        self.db = DB()
        self.screen_manager = None

    def build(self):
        self.theme_cls.primary_palette = "Blue"
        self.theme_cls.theme_style = "Light"
        
        # Load KV string
        Builder.load_string(KV)
        
        # Create screen manager
        self.screen_manager = Builder.load_string('''
ScreenManager:
    MainScreen:
    ReportFoundScreen:
    SearchScreen:
''')
        
        return self.screen_manager

    def on_start(self):
        # This runs after the UI is fully loaded
        self.populate_recent()

    def populate_recent(self):
        try:
            recent = self.db.recent_items(8)
            main_screen = self.screen_manager.get_screen('main')
            ml = main_screen.ids.recent_list
            ml.clear_widgets()
            
            if not recent:
                ml.add_widget(OneLineListItem(text="No recent items found"))
                return
                
            for r in recent:
                id_, code, itype, loc, date, img = r
                item = OneLineListItem(
                    text=f"{code} — {itype} ({loc})",
                    on_release=lambda x, _id=id_: self.open_item_preview(_id)
                )
                ml.add_widget(item)
        except Exception as e:
            print(f"Error populating recent items: {e}")
            Snackbar(text="Error loading recent items").open()

    def open_menu(self):
        Snackbar(text="Menu not implemented in prototype").open()

    def go_home(self):
        self.screen_manager.current = 'main'

    def open_report_found(self):
        self.screen_manager.current = 'report'

    def open_search(self):
        self.screen_manager.current = 'search'

    def scan_with_camera(self):
        try:
            out_path = os.path.join(self.user_data_dir, 'capture.jpg')
            camera.take_picture(filename=out_path, on_complete=lambda p: self._camera_callback(p))
            Snackbar(text="Opening camera...").open()
        except Exception as e:
            Snackbar(text=f"Camera error: {str(e)}").open()

    def _camera_callback(self, path):
        if not path:
            Snackbar(text="No photo taken").open()
            return
            
        Snackbar(text=f"Photo saved").open()
        threading.Thread(target=self._decode_and_fill, args=(path,)).start()

    def pick_image(self):
        try:
            filechooser.open_file(on_selection=lambda s: self._on_file_select(s))
        except Exception as e:
            Snackbar(text=f"Filechooser error: {str(e)}").open()

    def _on_file_select(self, selection):
        if not selection:
            return
        threading.Thread(target=self._decode_and_fill, args=(selection[0],)).start()

    def _decode_and_fill(self, path):
        self.show_loading("Decoding barcode...")
        try:
            pil = PILImage.open(path).convert('L')
            decoded = zbar_decode(pil)
            
            if not decoded:
                Snackbar(text="No barcode found").open()
                return
                
            # Find first Code39 barcode if available
            code = None
            for d in decoded:
                if 'CODE39' in d.type:
                    code = d.data.decode('utf-8')
                    break
                    
            if not code:
                code = decoded[0].data.decode('utf-8')
                
            self.fill_report_fields(code, path)
        except Exception as e:
            Snackbar(text=f"Decoding error: {str(e)}").open()
        finally:
            self.hide_loading()

    @mainthread
    def show_loading(self, text):
        self._loading_snackbar = Snackbar(text=text)
        self._loading_snackbar.open()

    @mainthread
    def hide_loading(self):
        if hasattr(self, '_loading_snackbar'):
            self._loading_snackbar.dismiss()

    @mainthread
    def fill_report_fields(self, code, image_path):
        report_screen = self.screen_manager.get_screen('report')
        report_screen.ids.code_field.text = code
        report_screen.ids.type_field.text = ""
        report_screen.ids.location_field.text = ""
        report_screen._picked_image = image_path
        self.screen_manager.current = 'report'

    def submit_found_item(self, code, item_type, location):
        if not code:
            Snackbar(text="Please provide a code").open()
            return
            
        report_screen = self.screen_manager.get_screen('report')
        image_path = getattr(report_screen, '_picked_image', None)
        
        contact_field = MDTextField(hint_text="Your contact number")
        dialog = MDDialog(
            title="Enter your contact",
            type="custom",
            content_cls=contact_field,
            buttons=[
                MDRaisedButton(text="Cancel", on_release=lambda x: dialog.dismiss()),
                MDRaisedButton(text="Submit", on_release=lambda x: self._submit_with_contact(
                    dialog, code, item_type, location, contact_field.text, image_path
                ))
            ]
        )
        dialog.open()

    def _submit_with_contact(self, dialog, code, item_type, location, contact, image_path):
        if not contact.strip():
            Snackbar(text="Contact number required").open()
            return
            
        self.db.add_item(
            code,
            item_type or "Unknown",
            location or "Unknown",
            contact,
            image_path
        )
        
        dialog.dismiss()
        self.go_home()
        self.populate_recent()
        Snackbar(text="Item reported successfully").open()

    def perform_search(self, code_like, item_type):
        results = self.db.search(code_like.strip(), item_type.strip())
        search_screen = self.screen_manager.get_screen('search')
        ml = search_screen.ids.results_list
        ml.clear_widgets()
        
        if not results:
            ml.add_widget(OneLineListItem(text="No matches found"))
            return
            
        for r in results:
            id_, code, itype, loc, date, unlocked, img = r
            item = OneLineListItem(
                text=f"{code} — {itype} ({loc})",
                on_release=lambda x, _id=id_: self.show_item_details(_id)
            )
            ml.add_widget(item)

    def show_item_details(self, item_id):
        item = self.db.get_item(item_id)
        if not item:
            Snackbar(text="Item not found").open()
            return
            
        id_, code, itype, loc, date, contact, unlocked, img = item
        
        if unlocked:
            content = f"Contact: {contact}"
        else:
            content = "Unlock to see contact details"
            
        buttons = [
            MDRaisedButton(text="Close", on_release=lambda x: dialog.dismiss())
        ]
        
        if not unlocked:
            buttons.append(
                MDRaisedButton(
                    text="Unlock (UGX 2000)",
                    on_release=lambda x: self.initiate_unlock(dialog, item_id)
                )
            )
            
        dialog = MDDialog(
            title=f"{code} - {itype}",
            text=f"Location: {loc}\nDate: {date.split('T')[0]}\n{content}",
            buttons=buttons
        )
        dialog.open()

    def initiate_unlock(self, dialog, item_id):
        # Simulate payment
        self.db.unlock_item(item_id)
        dialog.dismiss()
        item = self.db.get_item(item_id)
        Snackbar(text=f"Unlocked! Contact: {item[5]}").open()

if __name__ == '__main__':
    FindItApp().run()