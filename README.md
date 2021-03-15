# Strip Packer for php

## About
This repository contains an implementation for the strip packing problem in php.

The strip packing problem optimizes the placing of rectangles in a strip of fixed width and variable length, such that the overall length of the strip is minimised.

The 'Priority Heuristic' algorithm is implemented for the variant in which rotations are allowed and cuts have to follow the guillotine constraint.

**note:** This algorithm is heuristic which means that the outcome is possibly not the most optimal.

This algorithm is based on the following paper: [A priority heuristic for the guillotine rectangular packing problem](https://www.researchgate.net/publication/283094268_A_priority_heuristic_for_the_guillotine_rectangular_packing_problem)

```
@article{zhang2016priority,
  title={A priority heuristic for the guillotine rectangular packing problem},
  author={Zhang, Defu and Shi, Leyuan and Leung, Stephen CH and Wu, Tao},
  journal={Information Processing Letters},
  volume={116},
  number={1},
  pages={15--21},
  year={2016},
  publisher={Elsevier}
}
```

## The Code

The source code is heavily inspired by the python implementation: https://github.com/Mxbonn/strip-packing

However, it uses classes instead of lists for the rectangles and the assigned box positions. 
These classes are originally used in a 3D-packing algorithm, hence they are called `Box` (with width, depth and height) 
and `PackedBox` (with `x`, `y` and `z`). 
The 3rd dimension is ignored by this code, hence `Box.height` and `PackedBox.z` are not used in this algorithm.


## Demo

`demo/index.php` contains an example which can be run in the browser (run `php -S localhost:8001 -t demo/`) 
and direct your browser to `http://localhost:8001/`

Some visualized results are in the folder `demo_images`.

examples results:

![demo image](/demo_images/demo_30_42.svg?raw=true)

![demo image](/demo_images/demo_18_57.svg?raw=true)

![demo image](/demo_images/demo_19_40.svg?raw=true)


## Efficiency
It is important to realize that the algorithm is an heuristic and will not always find the optimal result.

The authors of the algorithm sort the rectangles internally by width, this is the most optimal for a lot of cases but not always.

**Know the difference between heuristic and optimal when using this algorithm.**
