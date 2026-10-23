import 'package:flutter/material.dart';

void main() {
  runApp(HelloWorld());
}

class HelloWorld extends StatefulWidget {
  const HelloWorld({super.key});

  @override
  State<HelloWorld> createState() => _HelloWorldState();
}

class _HelloWorldState extends State<HelloWorld> {
  int _counter = 0;
  String _message = 'HelloWorld';
  String _tapMessage = '';
  final TextEditingController _textController = TextEditingController();

  @override
  void dispose() {
    _textController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      home: Builder(
        builder: (context) {
          return Scaffold(
            appBar: AppBar(
              title: Text('Flutter widget')
            ),
            drawer: Drawer(
              child: ListView(
                children: [
                  DrawerHeader(
                    child: Text('Side menu')
                  ),
                  ListTile(
                    title: Text('Item 1'),
                    onTap: () {
                      setState(() {
                        _message = 'Item 1';
                      });
                    },
                  ),
                  ListTile(
                    title: Text('Item 2'),
                    onTap: () {
                      setState(() {
                        _message = 'Item 2';
                      });
                    }
                  )
                ]
              )
            ),
            body: SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Column(
                children: [
                  Text(_counter == 0 ? _message : '$_message+$_counter', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, fontStyle: FontStyle.italic, color: Colors.red)),
                  SizedBox(height: 20),
                  TextField(
                    controller: _textController,
                    decoration: InputDecoration(
                      border: OutlineInputBorder()
                    )
                  ),
                  SizedBox(height: 20),
                  ElevatedButton(
                    onPressed: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (context) => SecondRoute(message: _message, counter: _counter, inputText: _textController.text)
                        )
                      );
                    },
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text('Open route'),
                        SizedBox(width: 8),
                        Icon(Icons.arrow_forward)
                      ]
                    )
                  ),
                  SizedBox(height: 20),
                  GestureDetector(
                    onTap: () {
                      setState(() {
                        _tapMessage = 'Tap detected';
                      });
                    },
                    child: Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: Colors.blue,
                        borderRadius: BorderRadius.circular(8)
                      ),
                      child: Text('Tap')
                    ),
                  ),
                  Text(_tapMessage),
                  SizedBox(height: 20),
                  for(int i = 0; i <= 10; i++)
                  Card(
                    child: ListTile(
                      leading: CircleAvatar(
                        child: Text('$i')
                      ),
                      title: Text('Item $i'),
                      subtitle: Text('This is scrollable item $i')
                    )
                  )
                ]
              )
            ),
            floatingActionButton: FloatingActionButton(
              child: Icon(Icons.add),
              onPressed: () {
                setState(() {
                  _counter++;
                });
              }
            )
          );
        }
      )
    );
  }
}

class SecondRoute extends StatelessWidget {
  final int counter;
  final String message;
  final String inputText;
  const SecondRoute({super.key, required this.counter, required this.message, required this.inputText});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Center(
        child: Column(
          children: [
            Text(message),
            Text('$counter'),
            Text(inputText),
            SizedBox(height: 20),
            ElevatedButton.icon(
              icon: Icon(Icons.arrow_back),
              label: Text('Back'),
              onPressed: () {
                  Navigator.pop(context);
              }
            )
          ]
        )
      )
    );
  }
}
