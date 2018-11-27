
$categories = "blues", "classical", "country", "data", "folk", "jazz", "misc", "newage", "reggae", "rock", "soundtrack"

$current_path = Get-Location



Workflow Script-workflow {
    Param(
        [Parameter (Mandatory = $True)]
        [string] $current_path,

        [Parameter (Mandatory = $True)]
        [string] $category
    )

    Parallel {
        InlineScript {
            Set-Location $Using:current_path
            php -f "cddb_db_import_parallel.php" $Using:category asc
        }
        InlineScript {
            Set-Location $Using:current_path
            php -f "cddb_db_import_parallel.php" $Using:category desc
        }
    }
}

Workflow Category-workflow {
    Param(
        [Parameter (Mandatory = $True)]
        [string] $current_path,

        [Parameter (Mandatory = $True)]
        [array] $categories
    )

    ForEach -Parallel ($category in $categories) {
        Script-workflow -current_path $current_path -category $category
    }
}

Category-workflow -current_path $current_path -categories $categories
