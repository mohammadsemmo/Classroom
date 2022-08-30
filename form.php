<?php
    $title = "form";
    include('partials/header.php');
    if(!(isset($_SESSION["loggedin"])) || $_SESSION["loggedin"] === false || $_SESSION["type"] != 1){
      die("Unauthorized");
    }
    $userId = $_SESSION["id"];
    $formId = $_GET["id"];
    $query = "SELECT assignments.form_id as id, forms.title, forms.start_date_time,
                forms.end_date_time, classes.name as class_name
              FROM assignments
              JOIN forms ON assignments.form_id = forms.id
              JOIN classes ON classes.id = forms.class_id
              JOIN users_classes ON users_classes.class_id = classes.id
              JOIN users ON users.id = users_classes.user_id
              WHERE users.id = $userId AND forms.id = $formId";
    $result = mysqli_query($conn, $query);
    if (mysqli_num_rows($result) == 0) {
      die("Unauthorized");
    }

    $query = "SELECT * FROM forms WHERE id=$formId";
    $result = mysqli_fetch_assoc(mysqli_query($conn, $query));

    //get questions
    $query = "SELECT * FROM questions WHERE form_id=".$result["id"];
    $result = mysqli_query($conn, $query);

    $questions = [];
    while ($row = mysqli_fetch_assoc($result)) {
      $current = [];
      $current['id'] = $row['id'];
      $current['form_id'] = $row['form_id'];
      $current['question'] = $row['question'];
      $current['grade'] = $row['grade'];
      $query = "SELECT * FROM choices WHERE question_id=".$row["id"];
      $sub_result = mysqli_query($conn, $query);
      $choices = [];
      while ($sub_row = mysqli_fetch_assoc($sub_result)) {
        $choices[] = $sub_row;
      }
      $current["choices"] = $choices;
      $questions[] = $current;
    }
    $grade = 0;
   /*  $key = array_search('question 1', array_column($questions[], 'question'));
    echo $key; */

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
      //correct the form
      foreach ($_POST as $key => $value) {
        $key = array_slice(explode("-", $key), 1)[0];
        $value = array_slice(explode("-", $value), 1)[0];
        //get question index
        $questionIndex = array_search($key, array_column($questions, 'id'));
        //get grade of question
        $questionGrade = $questions[$questionIndex]["grade"];
        //get choice for question
        $choiceIndex = array_search(
          $value, array_column($questions[$questionIndex]["choices"], 'id')
        );
        //check if choice is correct
        if ($questions[$questionIndex]["choices"][$choiceIndex]["is_correct"])
          $grade += $questionGrade;
      }
      $query = "INSERT INTO form_submissions
                VALUES(
                  DEFAULT, $formId, $userId, DEFAULT, $grade
                )";
      mysqli_query($conn, $query);
      header('Location: ./index.php');
    }
    
?>

<div class="container p-3">
  <form action="form.php?id=<?=$formId?>" method="post">
    <?php foreach($questions as $q) { ?>
    <div class="border rounded bg-white p-3 mb-3">
      <p class="fs-4"><?= $q["question"] ." (grade: ".$q["grade"].")" ?></p>
      <?php foreach($q["choices"] as $c) {?>
      <div class="form-check fs-5">
        <input class="form-check-input" type="radio"
          name="question-<?= $q["id"] ?>" id="choice-<?= $c["id"] ?>"
          value="choice-<?= $c["id"] ?>">
        <label class="form-check-label" for="choice-<?= $c["id"] ?>">
          <?= $c["choice"] ?>
        </label>
      </div>
      <?php } ?>
    </div>
    <?php } ?>
    <input type="submit">
  </form>
</div>

</body>

</html>