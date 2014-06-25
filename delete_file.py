# coding: utf-8
import os

def findFileByExt(path, ext = 'pyc'):
    files = os.listdir(path)
    findList = []
    for f in files:
        fullPath = path + f
        if os.path.isdir(fullPath):
            findList.extend(findFileByExt(fullPath + '/', ext))
        elif os.path.isfile(fullPath):
            p, e = os.path.splitext(fullPath)
            if e == '.pyc':
                findList.append(fullPath)
    return findList

path = "D:/pyspace/web/"
files = findFileByExt(path)

print u'共找到', len(files), u'个文件'
for x in files:
    os.remove(x)
    print x