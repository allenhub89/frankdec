#!/usr/bin/env python

import sys
from collections import defaultdict

table = defaultdict(dict)
geneids = set()

for libid in sys.argv[1:]:
    with open(libid) as f:
        for line in f:
            parts = line.strip().split()
            table[parts[0]][libid] = parts[1]
            geneids.add(parts[0])

for geneid in geneids:
    line = [geneid]
    for libid in sys.argv[1:]:
        if libid in table[geneid]:
            line.append(table[geneid][libid])
        else:
            line.append('0')
    print '\t'.join(line)
