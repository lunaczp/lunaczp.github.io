# IO

## 不同软件的IO模型
- Memcache使用多线程实现，底层使用了libevent
- Nginx使用多进程实现，使用了`epoll`
- Redis单进程，底层自己封装了不同平台的io组件，如Linux下使用`epoll`，MacOs下使用`kqueue`

其他：
- Libevent是跨平台的IO库，提供了一层封装。在不同平台，底层使用不同的组件。比如在Linux下，底层使用`epoll`，在MacOS下，使用`kqueue`
