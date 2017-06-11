## Usage of php version
`php ./symb.php {d|e}  {filepath}`. First argument is action: *d*ecode or *e*ncode. While the second is path to filename (either relative or absolute).

For example: `php ./symb{2|3|4|5}.php d 5_comp` will create decoded file named `5_comp.dec` if there is no such filename or any unique name prefixed with `decoded` string e.g. `decodedYdbS`

