# https://github.com/xtremforce/AlfredWorkflowPython

import re
import array as arr
from xml.sax.saxutils import escape
from wcwidth import wcswidth
class AlfredWorkflow:
    def __init__(self):
        self.itemsArray = []
    
    def reset(self):
        self.itemsArray = []

    def reverse(self):
        self.itemsArray.reverse()

    def addItem(self, uid, title, subtitle, arg , icon=''):
        self.itemsArray.append({'uid': uid, 'arg': arg, 'title': title, 'subtitle': subtitle , 'icon': icon})
    
    def httpRequest(self, url, method='GET', data=None, headers=None):
        import requests
        import json

        if(url is None or url == '' or url.startswith('http://') == None or url.startswith('https://') == None):
            return None

        if(None==headers):
            headers = {
                'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/89.0.142.86 Safari/537.36',
            }
        

        method = method.upper()
        if method == 'POST':
            response = requests.post(url, headers=headers, data=data)
        elif method == 'PUT':
            response = requests.put(url, headers=headers, data=data)
        elif method == 'DELETE':
            response = requests.delete(url, headers=headers)
        else:
            response = requests.get(url, headers=headers)

        return response

    def __get_text_render_length(self,string):
        return wcswidth(string)

    def splitString(self,s, length):
        chunks, chunk = [], ""
        i = 0
        while i < len(s):
            if ('\u4e00' <= s[i] <= '\u9fef') or ('\u3000' <= s[i] <= '\u303f'):  # 当前字符是中文字符或标点
                if self.__get_text_render_length(chunk + s[i]) <= length:  # 如果加上这个字符后长度没有超过限制
                    chunk += s[i]
                else:  # 如果加上这个字符后长度超过了限制
                    chunks.append(chunk)
                    chunk = s[i]
            elif s[i].isalpha():  # 当前字符是英文字符
                word = re.search(r'\b\w+', s[i:]).group()  # 提取完整的英文单词
                if self.__get_text_render_length(chunk + word) <= length:  # 如果加上这个单词后长度没有超过限制
                    chunk += word
                    i += len(word) - 1  # i 跳过这个单词的剩余部分
                else:  # 如果加上这个单词后长度超过了限制
                    chunks.append(chunk)
                    chunk = word
                    i += len(word) - 1
            else:  # 当前字符是其他字符，包括英文标点等
                if self.__get_text_render_length(chunk + s[i]) <= length:  # 如果加上这个字符后长度没有超过限制
                    chunk += s[i]
                else:  # 如果加上这个字符后长度超过了限制
                    chunks.append(chunk)
                    chunk = s[i]
            if self.__get_text_render_length(chunk) == length:  # 如果当前行长度已经达到了限制
                chunks.append(chunk)
                chunk = ""
            i += 1

        if chunk:  # 把最后一行加到结果中
            chunks.append(chunk)

        return chunks

    def addLongText(self,text):
        self.reset()
        textArr = self.splitString(text, 65)
        for item in textArr:
            self.addItem(1,item, "", item)


    def escapeValues(self,obj):
        if isinstance(obj, dict):
            for key, value in obj.items():
                if isinstance(value, str):
                    obj[key] = escape(value)
        elif isinstance(obj, arr.array):
            for i, value in enumerate(obj):
                if isinstance(value, str):
                    obj[i] = escape(value)
        return obj

    def toxml(self):
        xml = '<?xml version="1.0" encoding="UTF-8"?><items>'
        for item in self.itemsArray:
            itemEscaped = self.escapeValues(item)

            if(item["uid"] is not None and item["uid"].strip() != ''):
                uidString = f' uid="{itemEscaped["uid"]}"'
            else:
                uidString = ''

            xml += f'<item'+ uidString + f' arg="{itemEscaped["arg"]}"><title>{itemEscaped["title"]}</title><subtitle>{itemEscaped["subtitle"]}</subtitle><icon>{itemEscaped["icon"]}</icon></item>'
        xml += '</items>'
        return xml

    def showMessage(self,msgTitle,msgSubtitle='',arg=''):
        self.reset()
        if(arg == ''):
            arg = msgTitle
        self.addItem(1,msgTitle, msgSubtitle, arg)
        self.show()

    def show(self):
        print(self.toxml())
