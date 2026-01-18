<?php
// config/quiz_data.php

$quiz_sets = [
    1 => [
        'title' => 'Basic Grammar',
        'desc' => 'Present Simple, Continuous, and basic sentence structure.',
        'questions' => [
            ['id'=>101, 'q'=>'Choose the correct sentence:', 'o'=>['He go to school.', 'He goes to school.', 'He going to school.'], 'c'=>1, 't'=>'grammar'],
            ['id'=>102, 'q'=>'Select the correct verb:', 'o'=>['They is happy.', 'They are happy.', 'They be happy.'], 'c'=>1, 't'=>'grammar'],
            ['id'=>103, 'q'=>'Complete: "She ___ playing tennis now."', 'o'=>['is', 'are', 'does'], 'c'=>0, 't'=>'tenses'],
            ['id'=>104, 'q'=>'Choose the negative:', 'o'=>['I not like it.', 'I don’t like it.', 'I no like it.'], 'c'=>1, 't'=>'grammar'],
            ['id'=>105, 'q'=>'Select the correct order:', 'o'=>['You are where from?', 'Where are you from?', 'Where you are from?'], 'c'=>1, 't'=>'grammar'],
        ]
    ],
    2 => [
        'title' => 'Past Tenses',
        'desc' => 'Past Simple, Past Continuous, and irregular verbs.',
        'questions' => [
            ['id'=>201, 'q'=>'I ___ to the cinema yesterday.', 'o'=>['go', 'went', 'have gone'], 'c'=>1, 't'=>'past_simple'],
            ['id'=>202, 'q'=>'She ___ sleeping when I called.', 'o'=>['was', 'were', 'is'], 'c'=>0, 't'=>'past_continuous'],
            ['id'=>203, 'q'=>'They ___ visit us last week.', 'o'=>['didn’t', 'don’t', 'haven’t'], 'c'=>0, 't'=>'past_simple'],
            ['id'=>204, 'q'=>'We ___ seen that movie before.', 'o'=>['has', 'did', 'had'], 'c'=>2, 't'=>'past_perfect'],
            ['id'=>205, 'q'=>'Why ___ you late?', 'o'=>['did', 'were', 'was'], 'c'=>1, 't'=>'past_simple'],
        ]
    ],
    3 => [
        'title' => 'Prepositions & Articles',
        'desc' => 'In, on, at, a, an, the usage.',
        'questions' => [
            ['id'=>301, 'q'=>'Let’s meet ___ 5 o\'clock.', 'o'=>['in', 'on', 'at'], 'c'=>2, 't'=>'prepositions'],
            ['id'=>302, 'q'=>'The book is ___ the table.', 'o'=>['in', 'on', 'at'], 'c'=>1, 't'=>'prepositions'],
            ['id'=>303, 'q'=>'I saw ___ elephant.', 'o'=>['a', 'an', 'the'], 'c'=>1, 't'=>'articles'],
            ['id'=>304, 'q'=>'She lives ___ London.', 'o'=>['in', 'on', 'at'], 'c'=>0, 't'=>'prepositions'],
            ['id'=>305, 'q'=>'___ sun is shining.', 'o'=>['A', 'An', 'The'], 'c'=>2, 't'=>'articles'],
        ]
    ],
    4 => [
        'title' => 'Vocabulary: Daily Life',
        'desc' => 'Common words for routine, home, and hobbies.',
        'questions' => [
            ['id'=>401, 'q'=>'Which is a fruit?', 'o'=>['Carrot', 'Apple', 'Potato'], 'c'=>1, 't'=>'vocab'],
            ['id'=>402, 'q'=>'We cook food in the ___.', 'o'=>['Bathroom', 'Kitchen', 'Bedroom'], 'c'=>1, 't'=>'vocab'],
            ['id'=>403, 'q'=>'Opposite of "Big":', 'o'=>['Huge', 'Small', 'Tall'], 'c'=>1, 't'=>'vocab'],
            ['id'=>404, 'q'=>'I brush my ___ every morning.', 'o'=>['hair', 'teeth', 'eyes'], 'c'=>1, 't'=>'vocab'],
            ['id'=>405, 'q'=>'You wear this on your feet:', 'o'=>['Hat', 'Gloves', 'Shoes'], 'c'=>2, 't'=>'vocab'],
        ]
    ],
    5 => [
        'title' => 'Vocabulary: Academic/Work',
        'desc' => 'Professional and formal terminology.',
        'questions' => [
            ['id'=>501, 'q'=>'A person who works with you is a:', 'o'=>['Enemy', 'Colleague', 'Relative'], 'c'=>1, 't'=>'vocab_work'],
            ['id'=>502, 'q'=>'To "submit" means to:', 'o'=>['Hand in', 'Throw away', 'Keep'], 'c'=>0, 't'=>'vocab_work'],
            ['id'=>503, 'q'=>'Synonym for "Important":', 'o'=>['Trivial', 'Significant', 'Optional'], 'c'=>1, 't'=>'vocab_work'],
            ['id'=>504, 'q'=>'A summary of your education and work:', 'o'=>['Recipe', 'CV / Resume', 'Receipt'], 'c'=>1, 't'=>'vocab_work'],
            ['id'=>505, 'q'=>'To "attend" a meeting means to:', 'o'=>['Cancel it', 'Go to it', 'Forget it'], 'c'=>1, 't'=>'vocab_work'],
        ]
    ],
];
?>
