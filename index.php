<?php
session_start();

// Security headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Initialize data files if they don't exist
$dataDir = __DIR__ . '/data';
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

$files = ['users.json', 'scores.json', 'chat.json'];
foreach ($files as $file) {
    $filepath = $dataDir . '/' . $file;
    if (!file_exists($filepath)) {
        file_put_contents($filepath, json_encode([]));
    }
}

// Initialize code snippets if they don't exist
$codeDir = __DIR__ . '/code_snippets';
if (!is_dir($codeDir)) {
    mkdir($codeDir, 0755, true);
    
    // Sample code snippets
    $snippets = [
        'python.json' => [
            'def fibonacci(n):
    if n <= 1:
        return n
    return fibonacci(n-1) + fibonacci(n-2)',
            'class Calculator:
    def __init__(self):
        self.result = 0
    
    def add(self, x, y):
        return x + y',
            'import json
import requests

def fetch_data(url):
    response = requests.get(url)
    return response.json()'
        ],
        'javascript.json' => [
            'function quickSort(arr) {
    if (arr.length <= 1) return arr;
    const pivot = arr[Math.floor(arr.length / 2)];
    const left = [], right = [], equal = [];
    
    for (let element of arr) {
        if (element < pivot) left.push(element);
        else if (element > pivot) right.push(element);
        else equal.push(element);
    }
    
    return [...quickSort(left), ...equal, ...quickSort(right)];
}',
            'const fetchUserData = async (userId) => {
    try {
        const response = await fetch(`/api/users/${userId}`);
        const userData = await response.json();
        return userData;
    } catch (error) {
        console.error("Error fetching user data:", error);
        return null;
    }
};',
            'class EventEmitter {
    constructor() {
        this.events = {};
    }
    
    on(event, callback) {
        if (!this.events[event]) {
            this.events[event] = [];
        }
        this.events[event].push(callback);
    }
    
    emit(event, data) {
        if (this.events[event]) {
            this.events[event].forEach(callback => callback(data));
        }
    }
}'
        ],
        'cpp.json' => [
            '#include <iostream>
#include <vector>
#include <algorithm>

class BinarySearch {
public:
    static int search(std::vector<int>& nums, int target) {
        int left = 0, right = nums.size() - 1;
        
        while (left <= right) {
            int mid = left + (right - left) / 2;
            if (nums[mid] == target) return mid;
            else if (nums[mid] < target) left = mid + 1;
            else right = mid - 1;
        }
        
        return -1;
    }
};',
            '#include <memory>
#include <string>

class LinkedListNode {
private:
    int data;
    std::shared_ptr<LinkedListNode> next;
    
public:
    LinkedListNode(int value) : data(value), next(nullptr) {}
    
    void setNext(std::shared_ptr<LinkedListNode> node) {
        next = node;
    }
    
    std::shared_ptr<LinkedListNode> getNext() const {
        return next;
    }
    
    int getData() const {
        return data;
    }
};'
        ],
        'go.json' => [
            'package main

import (
    "fmt"
    "sort"
)

func mergeSort(arr []int) []int {
    if len(arr) <= 1 {
        return arr
    }
    
    mid := len(arr) / 2
    left := mergeSort(arr[:mid])
    right := mergeSort(arr[mid:])
    
    return merge(left, right)
}

func merge(left, right []int) []int {
    result := make([]int, 0, len(left)+len(right))
    i, j := 0, 0
    
    for i < len(left) && j < len(right) {
        if left[i] <= right[j] {
            result = append(result, left[i])
            i++
        } else {
            result = append(result, right[j])
            j++
        }
    }
    
    result = append(result, left[i:]...)
    result = append(result, right[j:]...)
    
    return result
}',
            'type Stack struct {
    items []interface{}
}

func (s *Stack) Push(item interface{}) {
    s.items = append(s.items, item)
}

func (s *Stack) Pop() interface{} {
    if len(s.items) == 0 {
        return nil
    }
    
    index := len(s.items) - 1
    item := s.items[index]
    s.items = s.items[:index]
    
    return item
}

func (s *Stack) IsEmpty() bool {
    return len(s.items) == 0
}'
        ],
        'rust.json' => [
            'use std::collections::HashMap;

fn two_sum(nums: Vec<i32>, target: i32) -> Vec<i32> {
    let mut map = HashMap::new();
    
    for (i, num) in nums.iter().enumerate() {
        let complement = target - num;
        
        if let Some(&j) = map.get(&complement) {
            return vec![j as i32, i as i32];
        }
        
        map.insert(num, i);
    }
    
    vec![]
}',
            'struct BinaryTree<T> {
    value: T,
    left: Option<Box<BinaryTree<T>>>,
    right: Option<Box<BinaryTree<T>>>,
}

impl<T> BinaryTree<T> {
    fn new(value: T) -> Self {
        BinaryTree {
            value,
            left: None,
            right: None,
        }
    }
    
    fn insert_left(&mut self, value: T) {
        self.left = Some(Box::new(BinaryTree::new(value)));
    }
    
    fn insert_right(&mut self, value: T) {
        self.right = Some(Box::new(BinaryTree::new(value)));
    }
}'
        ]
    ];
    
    foreach ($snippets as $filename => $codes) {
        file_put_contents($codeDir . '/' . $filename, json_encode($codes, JSON_PRETTY_PRINT));
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SpeedTyper - Test Kecepatan Coding</title>
    <link rel="stylesheet" href="style.css">
    <meta name="csrf-token" content="<?php echo htmlspecialchars(bin2hex(random_bytes(32))); ?>">
</head>
<body>
    <?php include 'header.php'; ?>
    
   <main class="main-container">
        <!-- Language Selector -->
        <div class="language-selector">
            <label for="languageSelect">Pilih Bahasa:</label>
            <select id="languageSelect">
                <option value="python">Python</option>
                <option value="javascript">JavaScript</option>
                <option value="cpp">C++</option>
                <option value="go">Go</option>
                <option value="rust">Rust</option>
            </select>
            <button id="startTest" class="btn btn-primary">Mulai Test</button>
            <button id="resetTest" class="btn btn-secondary">Reset</button>
        </div>

        <!-- Stats Display -->
        <div class="stats-container">
            <div class="stat-item">
                <span class="stat-label">WPM:</span>
                <span id="wpm" class="stat-value">0</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Akurasi:</span>
                <span id="accuracy" class="stat-value">100%</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Kesalahan:</span>
                <span id="errors" class="stat-value">0</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Waktu:</span>
                <span id="timer" class="stat-value">0s</span>
            </div>
        </div>

        <!-- Integrated Code Display -->
        <div class="integrated-code-container" id="integratedContainer">
            <div id="codeDisplay" class="code-display-integrated">
                Pilih bahasa dan klik 'Mulai Test' untuk memulai...
            </div>
            <input type="text" class="hidden-input" id="hiddenInput" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
        </div>

        <!-- Results -->
        <div id="results" class="results-container" style="display: none;">
            <h3>Hasil Test</h3>
            <div class="result-stats">
                <div class="result-item">
                    <strong>WPM:</strong> <span id="finalWpm">0</span>
                </div>
                <div class="result-item">
                    <strong>Akurasi:</strong> <span id="finalAccuracy">100%</span>
                </div>
                <div class="result-item">
                    <strong>Kesalahan:</strong> <span id="finalErrors">0</span>
                </div>
                <div class="result-item">
                    <strong>Waktu:</strong> <span id="finalTime">0s</span>
                </div>
            </div>
            <button id="saveScore" class="btn btn-primary">Simpan Skor</button>
            <button id="newTest" class="btn btn-secondary">Test Baru</button>
        </div>
    </main>

    <!-- Login Modal -->
    <div id="loginModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Login Guest</h2>
            <form id="loginForm">
                <div class="form-group">
                    <label for="username">Nama:</label>
                    <input type="text" id="username" name="username" required maxlength="20" pattern="[a-zA-Z0-9_]{3,20}">
                    <small>3-20 karakter, hanya huruf, angka, dan underscore</small>
                </div>
                <button type="submit" class="btn btn-primary">Login</button>
            </form>
        </div>
    </div>


    <?php include 'footer.php'; ?>

    <script src="js/app.js"></script>
</body>
</html>